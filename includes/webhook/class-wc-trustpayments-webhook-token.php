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
 * Webhook processor to handle token state transitions.
 */
class WC_TrustPayments_Webhook_Token extends WC_TrustPayments_Webhook_Abstract {

    public function process(WC_TrustPayments_Webhook_Request $request){
        $token_service = WC_TrustPayments_Service_Token::instance();
		$token_service->update_token($request->get_space_id(), $request->get_entity_id());
	}
}