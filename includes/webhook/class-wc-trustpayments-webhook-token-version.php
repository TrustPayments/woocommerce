<?php
/**
 *
 * WC_TrustPayments_Webhook_Token_Version Class
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
 * Webhook processor to handle token version state transitions.
 */
class WC_TrustPayments_Webhook_Token_Version extends WC_TrustPayments_Webhook_Abstract {

	/**
	 * Process.
	 *
	 * @param WC_TrustPayments_Webhook_Request $request request.
	 * @return void
	 * @throws \TrustPayments\Sdk\ApiException ApiException.
	 * @throws \TrustPayments\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \TrustPayments\Sdk\VersioningException VersioningException.
	 */
	public function process( WC_TrustPayments_Webhook_Request $request ) {
		$token_service = WC_TrustPayments_Service_Token::instance();
		$token_service->update_token_version( $request->get_space_id(), $request->get_entity_id() );
	}
}
