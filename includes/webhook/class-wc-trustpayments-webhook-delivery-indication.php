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
 * Webhook processor to handle delivery indication state transitions.
 */
class WC_TrustPayments_Webhook_Delivery_Indication extends WC_TrustPayments_Webhook_Order_Related_Abstract {

	/**
	 *
	 * @see WC_TrustPayments_Webhook_Order_Related_Abstract::load_entity()
	 * @return \TrustPayments\Sdk\Model\DeliveryIndication
	 */
    protected function load_entity(WC_TrustPayments_Webhook_Request $request){
        $delivery_indication_service = new \TrustPayments\Sdk\Service\DeliveryIndicationService(WC_TrustPayments_Helper::instance()->get_api_client());
		return $delivery_indication_service->read($request->get_space_id(), $request->get_entity_id());
	}

	protected function get_order_id($delivery_indication){
		/* @var \TrustPayments\Sdk\Model\DeliveryIndication $delivery_indication */
        return WC_TrustPayments_Entity_Transaction_Info::load_by_transaction($delivery_indication->getTransaction()->getLinkedSpaceId(), $delivery_indication->getTransaction()->getId())->get_order_id();
	}

	protected function get_transaction_id($delivery_indication){
		/* @var \TrustPayments\Sdk\Model\DeliveryIndication $delivery_indication */
		return $delivery_indication->getLinkedTransaction();
	}

	protected function process_order_related_inner(WC_Order $order, $delivery_indication){
		/* @var \TrustPayments\Sdk\Model\DeliveryIndication $delivery_indication */
		switch ($delivery_indication->getState()) {
		    case \TrustPayments\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED:
				$this->review($order);
				break;
			default:
				// Nothing to do.
				break;
		}
	}

	protected function review(WC_Order $order){
		$status = apply_filters('wc_trustpayments_manual_task_status', 'trustp-manual', $order);
		$order->add_meta_data('_trustpayments_manual_check', true);
		$order->update_status($status, __('A manual decision about whether to accept the payment is required.', 'woo-trustpayments'));
	}
}