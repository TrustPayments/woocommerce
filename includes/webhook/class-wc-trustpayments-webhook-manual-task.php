<?php
/**
 *
 * WC_TrustPayments_Webhook_Manual_Task Class
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
 * Webhook processor to handle manual task state transitions.
 */
class WC_TrustPayments_Webhook_Manual_Task extends WC_TrustPayments_Webhook_Abstract {

	/**
	 * Updates the number of open manual tasks.
	 *
	 * @param WC_TrustPayments_Webhook_Request $request request.
	 */
	public function process( WC_TrustPayments_Webhook_Request $request ) {
		$manual_task_service = WC_TrustPayments_Service_Manual_Task::instance();
		$manual_task_service->update();
	}
}
