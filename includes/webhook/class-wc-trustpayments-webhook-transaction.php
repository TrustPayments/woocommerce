<?php
/**
 *
 * WC_TrustPayments_Webhook_Transaction Class
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
 * Webhook processor to handle transaction state transitions.
 */
class WC_TrustPayments_Webhook_Transaction extends WC_TrustPayments_Webhook_Order_Related_Abstract {


	/**
	 * Load entity.
	 *
	 * @param WC_TrustPayments_Webhook_Request $request request.
	 * @return object|\TrustPayments\Sdk\Model\Transaction
	 * @throws \TrustPayments\Sdk\ApiException ApiException.
	 * @throws \TrustPayments\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \TrustPayments\Sdk\VersioningException VersioningException.
	 */
	protected function load_entity( WC_TrustPayments_Webhook_Request $request ) {
		$transaction_service = new \TrustPayments\Sdk\Service\TransactionService( WC_TrustPayments_Helper::instance()->get_api_client() );
		return $transaction_service->read( $request->get_space_id(), $request->get_entity_id() );
	}

	/**
	 * Get order id.
	 *
	 * @param mixed $transaction transaction.
	 * @return int|string
	 */
	protected function get_order_id( $transaction ) {
		/* @var \TrustPayments\Sdk\Model\Transaction $transaction */
		return WC_TrustPayments_Entity_Transaction_Info::load_by_transaction( $transaction->getLinkedSpaceId(), $transaction->getId() )->get_order_id();
	}

	/**
	 * Get transaction id.
	 *
	 * @param mixed $transaction transaction.
	 * @return int
	 */
	protected function get_transaction_id( $transaction ) {
		/* @var \TrustPayments\Sdk\Model\Transaction $transaction */
		return $transaction->getId();
	}

	/**
	 * Process order related inner.
	 *
	 * @param WC_Order $order order.
	 * @param mixed    $transaction transaction.
	 * @return void
	 * @throws Exception Exception.
	 */
	protected function process_order_related_inner( WC_Order $order, $transaction ) {

		/* @var \TrustPayments\Sdk\Model\Transaction $transaction */
		$transaction_info = WC_TrustPayments_Entity_Transaction_Info::load_by_order_id( $order->get_id() );
		if ( $transaction->getState() != $transaction_info->get_state() ) {
			switch ( $transaction->getState() ) {
				case \TrustPayments\Sdk\Model\TransactionState::CONFIRMED:
				case \TrustPayments\Sdk\Model\TransactionState::PROCESSING:
					$this->confirm( $transaction, $order );
					break;
				case \TrustPayments\Sdk\Model\TransactionState::AUTHORIZED:
					$this->authorize( $transaction, $order );
					break;
				case \TrustPayments\Sdk\Model\TransactionState::DECLINE:
					$this->decline( $transaction, $order );
					break;
				case \TrustPayments\Sdk\Model\TransactionState::FAILED:
					$this->failed( $transaction, $order );
					break;
				case \TrustPayments\Sdk\Model\TransactionState::FULFILL:
					$this->authorize( $transaction, $order );
					$this->fulfill( $transaction, $order );
					break;
				case \TrustPayments\Sdk\Model\TransactionState::VOIDED:
					$this->voided( $transaction, $order );
					break;
				case \TrustPayments\Sdk\Model\TransactionState::COMPLETED:
					$this->authorize( $transaction, $order );
					$this->waiting( $transaction, $order );
					break;
				default:
					// Nothing to do.
					break;
			}
		}

		WC_TrustPayments_Service_Transaction::instance()->update_transaction_info( $transaction, $order );
	}

	/**
	 * Confirm.
	 *
	 * @param \TrustPayments\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order                                     $order order.
	 * @return void
	 */
	protected function confirm( \TrustPayments\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		if ( ! $order->get_meta( '_trustpayments_confirmed', true ) && ! $order->get_meta( '_trustpayments_authorized', true ) ) {
			do_action( 'wc_trustpayments_confirmed', $transaction, $order );
			$order->add_meta_data( '_trustpayments_confirmed', 'true', true );
			$status = apply_filters( 'wc_trustpayments_confirmed_status', 'trustp-redirected', $order );
			$order->update_status( $status );
			wc_maybe_reduce_stock_levels( $order->get_id() );
		}
	}

	/**
	 * Authorize.
	 *
	 * @param \TrustPayments\Sdk\Model\Transaction $transaction transaction.
	 * @param \WC_Order                                    $order order.
	 */
	protected function authorize( \TrustPayments\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		if ( ! $order->get_meta( '_trustpayments_authorized', true ) ) {
			do_action( 'wc_trustpayments_authorized', $transaction, $order );
			$status = apply_filters( 'wc_trustpayments_authorized_status', 'on-hold', $order );
			$order->add_meta_data( '_trustpayments_authorized', 'true', true );
			$order->update_status( $status );
			wc_maybe_reduce_stock_levels( $order->get_id() );
			if ( isset( WC()->cart ) ) {
				WC()->cart->empty_cart();
			}
		}
	}

	/**
	 * Waiting.
	 *
	 * @param \TrustPayments\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order                                     $order order.
	 * @return void
	 */
	protected function waiting( \TrustPayments\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		if ( ! $order->get_meta( '_trustpayments_manual_check', true ) ) {
			do_action( 'wc_trustpayments_completed', $transaction, $order );
			$status = apply_filters( 'wc_trustpayments_completed_status', 'trustp-waiting', $order );
			$order->update_status( $status );
		}
	}

	/**
	 * Decline.
	 *
	 * @param \TrustPayments\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order                                     $order order.
	 * @return void
	 */
	protected function decline( \TrustPayments\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		do_action( 'wc_trustpayments_declined', $transaction, $order );
		$status = apply_filters( 'wc_trustpayments_decline_status', 'cancelled', $order );
		$order->update_status( $status );
		WC_TrustPayments_Helper::instance()->maybe_restock_items_for_order( $order );
	}

	/**
	 * Failed.
	 *
	 * @param \TrustPayments\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order                                     $order order.
	 * @return void
	 */
	protected function failed( \TrustPayments\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		do_action( 'wc_trustpayments_failed', $transaction, $order );
		if ( $order->get_status( 'edit' ) == 'pending' || $order->get_status( 'edit' ) == 'trustp-redirected' ) {
			$status = apply_filters( 'wc_trustpayments_failed_status', 'failed', $order );
			$order->update_status( $status );
			WC_TrustPayments_Helper::instance()->maybe_restock_items_for_order( $order );
		}
	}

	/**
	 * Fulfill.
	 *
	 * @param \TrustPayments\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order                                     $order order.
	 * @return void
	 */
	protected function fulfill( \TrustPayments\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		do_action( 'wc_trustpayments_fulfill', $transaction, $order );
		// Sets the status to procesing or complete depending on items.
		$order->payment_complete( $transaction->getId() );

	}

	/**
	 * Voided.
	 *
	 * @param \TrustPayments\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order                                     $order order.
	 * @return void
	 */
	protected function voided( \TrustPayments\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		$status = apply_filters( 'wc_trustpayments_voided_status', 'cancelled', $order );
		$order->update_status( $status );
		do_action( 'wc_trustpayments_voided', $transaction, $order );
	}
}
