<?php
if (!defined('ABSPATH')) {
	exit(); // Exit if accessed directly.
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
 * Provider of payment method information from the gateway.
 */
class WC_TrustPayments_Provider_Payment_Method extends WC_TrustPayments_Provider_Abstract {

	protected function __construct(){
		parent::__construct('wc_trustpayments_payment_methods');
	}

	/**
	 * Returns the payment method by the given id.
	 *
	 * @param int $id
	 * @return \TrustPayments\Sdk\Model\PaymentMethod
	 */
	public function find($id){
		return parent::find($id);
	}

	/**
	 * Returns a list of payment methods.
	 *
	 * @return \TrustPayments\Sdk\Model\PaymentMethod[]
	 */
	public function get_all(){
		return parent::get_all();
	}

	protected function fetch_data(){
	    $method_service = new \TrustPayments\Sdk\Service\PaymentMethodService(WC_TrustPayments_Helper::instance()->get_api_client());
		return $method_service->all();
	}

	protected function get_id($entry){
		/* @var \TrustPayments\Sdk\Model\PaymentMethod $entry */
		return $entry->getId();
	}
}