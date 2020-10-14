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
 * Webhook processor to handle payment method configuration state transitions.
 */
class WC_TrustPayments_Webhook_Method_Configuration extends WC_TrustPayments_Webhook_Abstract {

	/**
	 * Synchronizes the payment method configurations on state transition.
	 *
	 * @param WC_TrustPayments_Webhook_Request $request
	 */
    public function process(WC_TrustPayments_Webhook_Request $request){
        $payment_method_configuration_service = WC_TrustPayments_Service_Method_Configuration::instance();
		$payment_method_configuration_service->synchronize();
	}
}