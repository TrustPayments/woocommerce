<?php
/**
 *
 * WC_TrustPayments_Provider_Payment_Connector Class
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
 * Provider of payment connector information from the gateway.
 */
class WC_TrustPayments_Provider_Payment_Connector extends WC_TrustPayments_Provider_Abstract {

	/**
	 * Construct.
	 */
	protected function __construct() {
		parent::__construct( 'wc_trustpayments_payment_connectors' );
	}

	/**
	 * Returns the payment connector by the given id.
	 *
	 * @param int $id Id.
	 * @return \TrustPayments\Sdk\Model\PaymentConnector
	 */
	public function find( $id ) {
		return parent::find( $id );
	}

	/**
	 * Returns a list of payment connectors.
	 *
	 * @return \TrustPayments\Sdk\Model\PaymentConnector[]
	 */
	public function get_all() {
		return parent::get_all();
	}

	/**
	 * Fetch data.
	 *
	 * @return array|\TrustPayments\Sdk\Model\PaymentConnector[]
	 * @throws \TrustPayments\Sdk\ApiException ApiException.
	 * @throws \TrustPayments\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \TrustPayments\Sdk\VersioningException VersioningException.
	 */
	protected function fetch_data() {
		$connector_service = new \TrustPayments\Sdk\Service\PaymentConnectorService( WC_TrustPayments_Helper::instance()->get_api_client() );
		return $connector_service->all();
	}

	/**
	 * Get id.
	 *
	 * @param mixed $entry entry.
	 * @return int|string
	 */
	protected function get_id( $entry ) {
		/* @var \TrustPayments\Sdk\Model\PaymentConnector $entry */
		return $entry->getId();
	}
}
