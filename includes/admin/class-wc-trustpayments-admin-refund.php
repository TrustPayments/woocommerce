<?php
if (!defined('ABSPATH')) {
	exit();
}
/**
 * Trust Payments WooCommerce
 *
 * This WooCommerce plugin enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */
/**
 * WC TrustPayments Admin Refund class
 */
class WC_TrustPayments_Admin_Refund {
	private static $refundable_states = array(
	    \TrustPayments\Sdk\Model\TransactionState::COMPLETED,
	    \TrustPayments\Sdk\Model\TransactionState::DECLINE,
	    \TrustPayments\Sdk\Model\TransactionState::FULFILL 
	);

	public static function init(){
		add_action('woocommerce_order_item_add_action_buttons', array(
			__CLASS__,
			'render_refund_button_state' 
		), 1000);
		
		add_action('woocommerce_create_refund', array(
			__CLASS__,
			'store_refund_in_globals' 
		), 10, 2);
		add_action('trustpayments_five_minutes_cron', array(
			__CLASS__,
			'update_refunds' 
		));
		
		add_action('woocommerce_admin_order_items_after_refunds', array(
			__CLASS__,
			'render_refund_states' 
		), 1000, 1);
		
		add_action('trustpayments_update_running_jobs', array(
			__CLASS__,
			'update_for_order' 
		));
	}

	public static function render_refund_button_state(WC_Order $order){
		$gateway = wc_get_payment_gateway_by_order($order);
		if ($gateway instanceof WC_TrustPayments_Gateway) {
		    $transaction_info = WC_TrustPayments_Entity_Transaction_Info::load_by_order_id($order->get_id());
			if (!in_array($transaction_info->get_state(), self::$refundable_states)) {
				echo '<span id="trustpayments-remove-refund" style="dispaly:none;"></span>';
			}
			else {
			    $existing_refund_job = WC_TrustPayments_Entity_Refund_Job::load_running_refund_for_transaction($transaction_info->get_space_id(), 
						$transaction_info->get_transaction_id());
				if ($existing_refund_job->get_id() > 0) {
					echo '<span class="trustpayments-action-in-progress">' . __('There is a refund in progress.', 'woo-trustpayments') . '</span>';
					echo '<button type="button" class="button trustpayments-update-order">' . __('Update', 'woo-trustpayments') . '</button>';
					echo '<span id="trustpayments-remove-refund" style="dispaly:none;"></span>';
				}
				echo '<span id="trustpayments-refund-restrictions" style="display:none;"></span>';
			}
		}
	}

	public static function render_refund_states($order_id){
	    $refunds = WC_TrustPayments_Entity_Refund_Job::load_refunds_for_order($order_id);
		if (!empty($refunds)) {
			echo '<tr style="display:none"><td>';
			foreach ($refunds as $refund) {
				echo '<div class="trustpayments-refund-status" data-refund-id="' . $refund->get_wc_refund_id() . '" data-refund-state="' .
				    $refund->get_state() . '" ></div>';
			}
			echo '</td></tr>';
		}
	}

	public static function store_refund_in_globals($refund, $request_args){
		$GLOBALS['trustpayments_refund_id'] = $refund->get_id();
		$GLOBALS['trustpayments_refund_request_args'] = $request_args;
	}

	public static function execute_refund(WC_Order $order, WC_Order_Refund $refund){
		$current_refund_job_id = null;
		$transaction_info = null;
		$refund_service = WC_TrustPayments_Service_Refund::instance();
		try {
		    WC_TrustPayments_Helper::instance()->start_database_transaction();
			$transaction_info = WC_TrustPayments_Entity_Transaction_Info::load_by_order_id($order->get_id());
			if (!$transaction_info->get_id()) {
				throw new Exception(__('Could not load corresponding transaction', 'woo-trustpayments'));
			}
			
			WC_TrustPayments_Helper::instance()->lock_by_transaction_id($transaction_info->get_space_id(), $transaction_info->get_transaction_id());
			
			if (WC_TrustPayments_Entity_Refund_Job::count_running_refund_for_transaction($transaction_info->get_space_id(), 
					$transaction_info->get_transaction_id()) > 0) {
				throw new Exception(__('Please wait until the pending refund is processed.', 'woo-trustpayments'));
			}
			$refund_create = $refund_service->create($order, $refund);
			$refund_job = self::create_refund_job($order, $refund, $refund_create);
			$current_refund_job_id = $refund_job->get_id();
			
			$refund->add_meta_data('_trustpayments_refund_job_id', $refund_job->get_id());
			$refund->set_status("pending");
			$refund->save();
			WC_TrustPayments_Helper::instance()->commit_database_transaction();
		}
		catch (Exception $e) {
		    WC_TrustPayments_Helper::instance()->rollback_database_transaction();
			throw $e;
		}
		self::send_refund($current_refund_job_id);
	}

	protected static function send_refund($refund_job_id){
		$refund_job = WC_TrustPayments_Entity_Refund_Job::load_by_id($refund_job_id);
		WC_TrustPayments_Helper::instance()->start_database_transaction();
		WC_TrustPayments_Helper::instance()->lock_by_transaction_id($refund_job->get_space_id(), $refund_job->get_transaction_id());
		//Reload void job;
		$refund_job = WC_TrustPayments_Entity_Refund_Job::load_by_id($refund_job_id);
		
		if ($refund_job->get_state() != WC_TrustPayments_Entity_Refund_Job::STATE_CREATED) {
			//Already sent in the meantime
		    WC_TrustPayments_Helper::instance()->rollback_database_transaction();
			return;
		}
		try {
		    $refund_service = WC_TrustPayments_Service_Refund::instance();
			$executed_refund = $refund_service->refund($refund_job->get_space_id(), $refund_job->get_refund());
			$refund_job->set_state(WC_TrustPayments_Entity_Refund_Job::STATE_SENT);
			
			if ($executed_refund->getState() == \TrustPayments\Sdk\Model\RefundState::PENDING) {
			    $refund_job->set_state(WC_TrustPayments_Entity_Refund_Job::STATE_PENDING);
			}
			$refund_job->save();
			WC_TrustPayments_Helper::instance()->commit_database_transaction();
		}
		catch (\TrustPayments\Sdk\ApiException $e) {
		    if ($e->getResponseObject() instanceof \TrustPayments\Sdk\Model\ClientError) {
		        $refund_job->set_failure_reason(
		            array(
		                'en-US' => $e->getResponseObject()->getMessage()));
		        $refund_job->set_state(WC_TrustPayments_Entity_Refund_Job::STATE_FAILURE);
		        $refund_job->save();
		        WC_TrustPayments_Helper::instance()->commit_database_transaction();
		    }
		    else{
		        $refund_job->save();
		        WC_TrustPayments_Helper::instance()->commit_database_transaction();
		        WooCommerce_TrustPayments::instance()->log('Error sending refund. '.$e->getMessage(), WC_Log_Levels::INFO);
		        throw new Exception(sprintf(__('There has been an error while sending the refund to the gateway. Error: %s', 'woo-trustpayments'), $e->getMessage()));
		    }
		}
		catch (Exception $e) {
			$refund_job->save();
			WC_TrustPayments_Helper::instance()->commit_database_transaction();
			WooCommerce_TrustPayments::instance()->log('Error sending refund. '.$e->getMessage(), WC_Log_Levels::INFO);
			throw new Exception(sprintf(__('There has been an error while sending the refund to the gateway. Error: %s', 'woo-trustpayments'), $e->getMessage()));
		}
	}

	public static function update_for_order(WC_Order $order){
	    
	    $transaction_info = WC_TrustPayments_Entity_Transaction_Info::load_by_order_id($order->get_id());
	    $refund_job = WC_TrustPayments_Entity_Refund_Job::load_running_refund_for_transaction($transaction_info->get_space_id(), $transaction_info->get_transaction_id());
		
		if ($refund_job->get_state() == WC_TrustPayments_Entity_Refund_Job::STATE_CREATED) {
			self::send_refund($refund_job->get_id());
		}
	}

	public static function update_refunds(){
	    $to_process = WC_TrustPayments_Entity_Refund_Job::load_not_sent_job_ids();
		foreach ($to_process as $id) {
			try {
				self::send_refund($id);
			}
			catch (Exception $e) {
				$message = sprintf(__('Error updating refund job with id %d: %s', 'woo-trustpayments'), $id, $e->getMessage());
				WooCommerce_TrustPayments::instance()->log($message, WC_Log_Levels::ERROR);
			}
		}
	}

	/**
	 * Creates a new refund job for the given order and refund.
	 *
	 * @param WC_Order $order
	 * @param WC_Order_Refund $refund
	 * @param \TrustPayments\Sdk\Model\RefundCreate $refund_create
	 * @return WC_TrustPayments_Entity_Refund_Job
	 */
	private static function create_refund_job(WC_Order $order, WC_Order_Refund $refund, \TrustPayments\Sdk\Model\RefundCreate $refund_create){
	    $transaction_info = WC_TrustPayments_Entity_Transaction_Info::load_by_order_id($order->get_id());
	    $refund_job = new WC_TrustPayments_Entity_Refund_Job();
	    $refund_job->set_state(WC_TrustPayments_Entity_Refund_Job::STATE_CREATED);
		$refund_job->set_wc_refund_id($refund->get_id());
		$refund_job->set_order_id($order->get_id());
		$refund_job->set_space_id($transaction_info->get_space_id());
		$refund_job->set_transaction_id($refund_create->getTransaction());
		$refund_job->set_external_id($refund_create->getExternalId());
		$refund_job->set_refund($refund_create);
		$refund_job->save();
		return $refund_job;
	}
}
WC_TrustPayments_Admin_Refund::init();
