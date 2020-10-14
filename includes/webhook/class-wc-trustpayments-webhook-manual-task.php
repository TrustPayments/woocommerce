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
 * Webhook processor to handle manual task state transitions.
 */
class WC_TrustPayments_Webhook_Manual_Task extends WC_TrustPayments_Webhook_Abstract {

	/**
	 * Updates the number of open manual tasks.
	 *
	 * @param WC_TrustPayments_Webhook_Request $request
	 */
    public function process(WC_TrustPayments_Webhook_Request $request){
        $manual_task_service = WC_TrustPayments_Service_Manual_Task::instance();
		$manual_task_service->update();
	}
}