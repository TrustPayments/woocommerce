<?php
/**
 *
 * WC_TrustPayments_Webhook_Method_Configuration Class
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
 * Webhook processor to handle payment method configuration state transitions.
 */
class WC_TrustPayments_Webhook_Method_Configuration extends WC_TrustPayments_Webhook_Abstract {

	/**
	 * Synchronizes the payment method configurations on state transition.
	 *
	 * @param WC_TrustPayments_Webhook_Request $request request.
	 */
	public function process( WC_TrustPayments_Webhook_Request $request ) {
		$payment_method_configuration_service = WC_TrustPayments_Service_Method_Configuration::instance();
		$payment_method_configuration_service->synchronize();
	}
}
