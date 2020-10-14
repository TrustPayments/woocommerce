<?php
if (!defined('ABSPATH')) {
	exit();
}
/**
 * Trust Payments WooCommerce
 *
 * This WooCommerce plugin enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author wallee AG (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */
/**
 * This class handles the customer returns
 */
class WC_TrustPayments_Return_Handler {

	public static function init(){
		add_action('woocommerce_api_trustpayments_return', array(
			__CLASS__,
			'process' 
		));
	}

	public static function process(){
		if (isset($_GET['action']) && isset($_GET['order_key']) && isset($_GET['order_id'])) {
			$order_key = $_GET['order_key'];
			$order_id = absint($_GET['order_id']);
			$order = WC_Order_Factory::get_order($order_id);
			$action = $_GET['action'];
			if ($order->get_id() === $order_id && $order->get_order_key() === $order_key) {
				switch ($action) {
					case 'success':
						self::process_success($order);
						break;
					case 'failure':
						self::process_failure($order);
						break;
					default:
				}
			}
		}
		wp_redirect(home_url('/'));
		exit();
	}

	protected static function process_success(WC_Order $order){
	    $transaction_service = WC_TrustPayments_Service_Transaction::instance();
		
		$transaction_service->wait_for_transaction_state($order, 
				array(
				    \TrustPayments\Sdk\Model\TransactionState::AUTHORIZED,
				    \TrustPayments\Sdk\Model\TransactionState::COMPLETED,
				    \TrustPayments\Sdk\Model\TransactionState::FULFILL,
				), 5);
		$gateway = wc_get_payment_gateway_by_order($order);
		$url = apply_filters('wc_trustpayments_success_url', $gateway->get_return_url($order), $order);		
		wp_redirect($url);
		exit();
	}

	protected static function process_failure(WC_Order $order){
	    $transaction_service = WC_TrustPayments_Service_Transaction::instance();
		$transaction_service->wait_for_transaction_state($order, array(
		    \TrustPayments\Sdk\Model\TransactionState::FAILED 
		), 5);
		$transaction = WC_TrustPayments_Entity_Transaction_Info::load_newest_by_mapped_order_id($order->get_id());
		if($transaction->get_state() ==  \TrustPayments\Sdk\Model\TransactionState::FAILED ){
		    WC()->session->set( 'order_awaiting_payment', $order->get_id());
		}		
		$user_message = $transaction->get_user_failure_message();
		$failure_reason = $transaction->get_failure_reason();
		if(empty($user_message) && $failure_reason !== null){
		    $user_message = $failure_reason;
		}
		if (!empty($user_message)) {
		    WC()->session->set( 'trustpayments_failure_message', $user_message );
		}
		if($order->get_meta('_trustpayments_pay_for_order', true, 'edit')){
		    $url = apply_filters('wc_trustpayments_pay_failure_url', $order->get_checkout_payment_url(false), $order);
		    wp_redirect($url);
		}
		else{
		    $url = apply_filters('wc_trustpayments_checkout_failure_url', wc_get_checkout_url(), $order);		
		    wp_redirect($url);
		}
		exit();
	}
}
WC_TrustPayments_Return_Handler::init();