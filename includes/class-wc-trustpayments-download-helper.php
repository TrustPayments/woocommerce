<?php
/**
 *
 * WC_TrustPayments_Download_Helper Class
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
 * Class WC_TrustPayments_Download_Helper.
 *
 * @class WC_TrustPayments_Download_Helper
 */
/**
 * This class provides function to download documents from Trust Payments
 */
class WC_TrustPayments_Download_Helper {

	/**
	 * Downloads the transaction's invoice PDF document.
	 *
	 * @param int $order_id order id.
	 */
	public static function download_invoice( $order_id ) {
		$transaction_info = WC_TrustPayments_Entity_Transaction_Info::load_by_order_id( $order_id );
		if ( ! is_null( $transaction_info->get_id() ) && in_array(
			$transaction_info->get_state(),
			array(
				\TrustPayments\Sdk\Model\TransactionState::COMPLETED,
				\TrustPayments\Sdk\Model\TransactionState::FULFILL,
				\TrustPayments\Sdk\Model\TransactionState::DECLINE,
			)
		) ) {

			$service = new \TrustPayments\Sdk\Service\TransactionService( WC_TrustPayments_Helper::instance()->get_api_client() );
			$document = $service->getInvoiceDocument( $transaction_info->get_space_id(), $transaction_info->get_transaction_id() );
			self::download( $document );
		}
	}

	/**
	 * Downloads the transaction's packing slip PDF document.
	 *
	 * @param int $order_id order id.
	 */
	public static function download_packing_slip( $order_id ) {
		$transaction_info = WC_TrustPayments_Entity_Transaction_Info::load_by_order_id( $order_id );
		if ( ! is_null( $transaction_info->get_id() ) && $transaction_info->get_state() == \TrustPayments\Sdk\Model\TransactionState::FULFILL ) {

			$service = new \TrustPayments\Sdk\Service\TransactionService( WC_TrustPayments_Helper::instance()->get_api_client() );
			$document = $service->getPackingSlip( $transaction_info->get_space_id(), $transaction_info->get_transaction_id() );
			self::download( $document );
		}
	}

	/**
	 * Sends the data received by calling the given path to the browser and ends the execution of the script
	 *
	 * @param \TrustPayments\Sdk\Model\RenderedDocument $document document.
	 */
	public static function download( \TrustPayments\Sdk\Model\RenderedDocument $document ) {
		header( 'Pragma: public' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . $document->getTitle() . '.pdf"' );
		header( 'Content-Description: ' . $document->getTitle() );
		// phpcs:ignore
	    	echo base64_decode( $document->getData() );
		exit();
	}
}
