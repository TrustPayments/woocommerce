<?php
/**
 *
 * WC_TrustPayments_Provider_Currency Class
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
 * Provider of currency information from the gateway.
 */
class WC_TrustPayments_Provider_Currency extends WC_TrustPayments_Provider_Abstract {

	/**
	 * Construct.
	 */
	protected function __construct() {
		parent::__construct( 'wc_trustpayments_currencies' );
	}

	/**
	 * Returns the currency by the given code.
	 *
	 * @param string $code code.
	 * @return \TrustPayments\Sdk\Model\RestCurrency
	 */
	public function find( $code ) {
		return parent::find( $code );
	}

	/**
	 * Returns a list of currencies.
	 *
	 * @return \TrustPayments\Sdk\Model\RestCurrency[]
	 */
	public function get_all() {
		return parent::get_all();
	}


	/**
	 * Fetch data.
	 *
	 * @return array|\TrustPayments\Sdk\Model\RestCurrency[]
	 * @throws \TrustPayments\Sdk\ApiException ApiException.
	 * @throws \TrustPayments\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \TrustPayments\Sdk\VersioningException VersioningException.
	 */
	protected function fetch_data() {
		$currency_service = new \TrustPayments\Sdk\Service\CurrencyService( WC_TrustPayments_Helper::instance()->get_api_client() );
		return $currency_service->all();
	}

	/**
	 * Get id.
	 *
	 * @param mixed $entry entry.
	 * @return string
	 */
	protected function get_id( $entry ) {
		/* @var \TrustPayments\Sdk\Model\RestCurrency $entry */
		return $entry->getCurrencyCode();
	}
}
