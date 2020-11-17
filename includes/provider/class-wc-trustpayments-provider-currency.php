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
 * Provider of currency information from the gateway.
 */
class WC_TrustPayments_Provider_Currency extends WC_TrustPayments_Provider_Abstract {

	protected function __construct(){
		parent::__construct('wc_trustpayments_currencies');
	}

	/**
	 * Returns the currency by the given code.
	 *
	 * @param string $code
	 * @return \TrustPayments\Sdk\Model\RestCurrency
	 */
	public function find($code){
		return parent::find($code);
	}

	/**
	 * Returns a list of currencies.
	 *
	 * @return \TrustPayments\Sdk\Model\RestCurrency[]
	 */
	public function get_all(){
		return parent::get_all();
	}

	protected function fetch_data(){
	    $currency_service = new \TrustPayments\Sdk\Service\CurrencyService(WC_TrustPayments_Helper::instance()->get_api_client());
		return $currency_service->all();
	}

	protected function get_id($entry){
		/* @var \TrustPayments\Sdk\Model\RestCurrency $entry */
		return $entry->getCurrencyCode();
	}
}