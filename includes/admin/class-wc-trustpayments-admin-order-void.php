<?php
/**
 *
 * WC_TrustPayments_Admin_Order_Void Class
 *
 * TrustPayments
 * This plugin will add support for all TrustPayments payments methods and connect the TrustPayments servers to your WooCommerce webshop (https://www.trustpayments.com/).
 *
 * @category Class
 * @package  TrustPayments
 * @author   wallee AG (http://www.wallee.com/)
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * WC TrustPayments Admin Order Void class
 */
class WC_TrustPayments_Admin_Order_Void {

	/**
	 * Init.
	 *
	 * @return void
	 */
	public static function init() {
		add_action(
			'woocommerce_order_item_add_line_buttons',
			array(
				__CLASS__,
				'render_execute_void_button',
			)
		);

		add_action(
			'wp_ajax_woocommerce_trustpayments_execute_void',
			array(
				__CLASS__,
				'execute_void',
			)
		);

		add_action(
			'trustpayments_five_minutes_cron',
			array(
				__CLASS__,
				'update_voids',
			)
		);

		add_action(
			'trustpayments_update_running_jobs',
			array(
				__CLASS__,
				'update_for_order',
			)
		);
	}

	/**
	 * Render execute void button.
	 *
	 * @param WC_Order $order order.
	 * @return void
	 */
	public static function render_execute_void_button( WC_Order $order ) {
		$gateway = wc_get_payment_gateway_by_order( $order );
		if ( $gateway instanceof WC_TrustPayments_Gateway ) {
			$transaction_info = WC_TrustPayments_Entity_Transaction_Info::load_by_order_id( $order->get_id() );
			if ( $transaction_info->get_state() === \TrustPayments\Sdk\Model\TransactionState::AUTHORIZED ) {
				echo '<button type="button" class="button trustpayments-void-button action-trustpayments-void-cancel" style="display:none">' .
					esc_html__( 'Cancel', 'woo-trustpayments' ) . '</button>';
				echo '<button type="button" class="button button-primary trustpayments-void-button action-trustpayments-void-execute" style="display:none">' .
					esc_html__( 'Execute Void', 'woo-trustpayments' ) . '</button>';
				echo '<label for="restock_voided_items" style="display:none">' . esc_html__( 'Restock items', 'woo-trustpayments' ) . '</label>';
				echo '<input type="checkbox" id="restock_voided_items" name="restock_voided_items" checked="checked" style="display:none">';
			}
		}
	}

	/**
	 * Execute void.
	 *
	 * @return void
	 * @throws Exception Exception.
	 */
	public static function execute_void() {
		ob_start();

		check_ajax_referer( 'order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( -1 );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : null;

		$restock_void_items = isset( $_POST['restock_voided_items'] ) && 'true' === sanitize_text_field( wp_unslash( $_POST['restock_voided_items'] ) );
		$current_void_id    = null;
		try {
			WC_TrustPayments_Helper::instance()->start_database_transaction();
			$transaction_info = WC_TrustPayments_Entity_Transaction_Info::load_by_order_id( $order_id );
			if ( ! $transaction_info->get_id() ) {
				throw new Exception( __( 'Could not load corresponding transaction' ) );
			}

			WC_TrustPayments_Helper::instance()->lock_by_transaction_id( $transaction_info->get_space_id(), $transaction_info->get_transaction_id() );
			$transaction_info = WC_TrustPayments_Entity_Transaction_Info::load_by_transaction(
				$transaction_info->get_space_id(),
				$transaction_info->get_transaction_id(),
				$transaction_info->get_space_id()
			);

			if ( $transaction_info->get_state() !== \TrustPayments\Sdk\Model\TransactionState::AUTHORIZED ) {
				throw new Exception( __( 'The transaction is not in a state to be voided.', 'woo-trustpayments' ) );
			}

			if ( WC_TrustPayments_Entity_Void_Job::count_running_void_for_transaction(
				$transaction_info->get_space_id(),
				$transaction_info->get_transaction_id()
			) > 0 ) {
				throw new Exception( __( 'Please wait until the existing void is processed.', 'woo-trustpayments' ) );
			}
			if ( WC_TrustPayments_Entity_Completion_Job::count_running_completion_for_transaction(
				$transaction_info->get_space_id(),
				$transaction_info->get_transaction_id()
			) > 0 ) {
				throw new Exception( __( 'There is a completion in process. The order can not be voided.', 'woo-trustpayments' ) );
			}

			$void_job = new WC_TrustPayments_Entity_Void_Job();
			$void_job->set_restock( $restock_void_items );
			$void_job->set_space_id( $transaction_info->get_space_id() );
			$void_job->set_transaction_id( $transaction_info->get_transaction_id() );
			$void_job->set_state( WC_TrustPayments_Entity_Void_Job::STATE_CREATED );
			$void_job->set_order_id( $order_id );
			$void_job->save();
			$current_void_id = $void_job->get_id();
			WC_TrustPayments_Helper::instance()->commit_database_transaction();
		} catch ( Exception $e ) {
			WC_TrustPayments_Helper::instance()->rollback_database_transaction();
			wp_send_json_error(
				array(
					'error' => $e->getMessage(),
				)
			);
			return;
		}

		try {
			self::send_void( $current_void_id );
			wp_send_json_success(
				array(
					'message' => __( 'The transaction is updated automatically once the result is available.', 'woo-trustpayments' ),
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'error' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Send void.
	 *
	 * @param mixed $void_job_id void job id.
	 * @return void
	 * @throws \TrustPayments\Sdk\ApiException ApiException.
	 * @throws \TrustPayments\Sdk\Http\ConnectionException ConnectionException.
	 */
	protected static function send_void( $void_job_id ) {
		$void_job = WC_TrustPayments_Entity_Void_Job::load_by_id( $void_job_id );
		WC_TrustPayments_Helper::instance()->start_database_transaction();
		WC_TrustPayments_Helper::instance()->lock_by_transaction_id( $void_job->get_space_id(), $void_job->get_transaction_id() );
		// Reload void job.
		$void_job = WC_TrustPayments_Entity_Void_Job::load_by_id( $void_job_id );
		if ( $void_job->get_state() !== WC_TrustPayments_Entity_Void_Job::STATE_CREATED ) {
			// Already sent in the meantime.
			WC_TrustPayments_Helper::instance()->rollback_database_transaction();
			return;
		}
		try {
			$void_service = new \TrustPayments\Sdk\Service\TransactionVoidService( WC_TrustPayments_Helper::instance()->get_api_client() );

			$void = $void_service->voidOnline( $void_job->get_space_id(), $void_job->get_transaction_id() );
			$void_job->set_void_id( $void->getId() );
			$void_job->set_state( WC_TrustPayments_Entity_Void_Job::STATE_SENT );
			$void_job->save();
			WC_TrustPayments_Helper::instance()->commit_database_transaction();
		} catch ( \TrustPayments\Sdk\ApiException $e ) {
			if ( $e->getResponseObject() instanceof \TrustPayments\Sdk\Model\ClientError ) {
				$void_job->set_state( WC_TrustPayments_Entity_Void_Job::STATE_DONE );
				$void_job->save();
				WC_TrustPayments_Helper::instance()->commit_database_transaction();
			} else {
				$void_job->save();
				WC_TrustPayments_Helper::instance()->commit_database_transaction();
				WooCommerce_TrustPayments::instance()->log( 'Error sending void. ' . $e->getMessage(), WC_Log_Levels::INFO );
				throw $e;
			}
		} catch ( Exception $e ) {
			$void_job->save();
			WC_TrustPayments_Helper::instance()->commit_database_transaction();
			WooCommerce_TrustPayments::instance()->log( 'Error sending void. ' . $e->getMessage(), WC_Log_Levels::INFO );
			throw $e;
		}
	}

	/**
	 * Update for order.
	 *
	 * @param WC_Order $order Order.
	 * @return void
	 * @throws \TrustPayments\Sdk\ApiException ApiException.
	 * @throws \TrustPayments\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \TrustPayments\Sdk\VersioningException VersioningException.
	 */
	public static function update_for_order( WC_Order $order ) {
		$transaction_info = WC_TrustPayments_Entity_Transaction_Info::load_by_order_id( $order->get_id() );
		$void_job         = WC_TrustPayments_Entity_Void_Job::load_running_void_for_transaction( $transaction_info->get_space_id(), $transaction_info->get_transaction_id() );

		if ( $void_job->get_state() === WC_TrustPayments_Entity_Void_Job::STATE_CREATED ) {
			self::send_void( $void_job->get_id() );
		}
	}

	/**
	 * Update voids.
	 *
	 * @return void
	 */
	public static function update_voids() {
		$to_process = WC_TrustPayments_Entity_Void_Job::load_not_sent_job_ids();
		foreach ( $to_process as $id ) {
			try {
				self::send_void( $id );
			} catch ( Exception $e ) {
				/* translators: %1$d: id, %2$s: message */
				$message = sprintf( __( 'Error updating void job with id %1$d: %2$s', 'woo-trustpayments' ), $id, $e->getMessage() );
				WooCommerce_TrustPayments::instance()->log( $message, WC_Log_Levels::ERROR );
			}
		}
	}
}
WC_TrustPayments_Admin_Order_Void::init();
