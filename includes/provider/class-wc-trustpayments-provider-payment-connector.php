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
 * Provider of payment connector information from the gateway.
 */
class WC_TrustPayments_Provider_Payment_Connector extends WC_TrustPayments_Provider_Abstract {

	protected function __construct(){
		parent::__construct('wc_trustpayments_payment_connectors');
	}

	/**
	 * Returns the payment connector by the given id.
	 *
	 * @param int $id
	 * @return \TrustPayments\Sdk\Model\PaymentConnector
	 */
	public function find($id){
		return parent::find($id);
	}

	/**
	 * Returns a list of payment connectors.
	 *
	 * @return \TrustPayments\Sdk\Model\PaymentConnector[]
	 */
	public function get_all(){
		return parent::get_all();
	}

	protected function fetch_data(){
	    $connector_service = new \TrustPayments\Sdk\Service\PaymentConnectorService(WC_TrustPayments_Helper::instance()->get_api_client());
		return $connector_service->all();
	}

	protected function get_id($entry){
		/* @var \TrustPayments\Sdk\Model\PaymentConnector $entry */
		return $entry->getId();
	}
}