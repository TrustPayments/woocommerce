<?php
/**
 *
 * WC_TrustPayments_Provider_Label_Description Class
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
 * Provider of label descriptor information from the gateway.
 */
class WC_TrustPayments_Provider_Label_Description extends WC_TrustPayments_Provider_Abstract {

	/**
	 * Construct.
	 */
	protected function __construct() {
		parent::__construct( 'wc_trustpayments_label_descriptions' );
	}

	/**
	 * Returns the label descriptor by the given code.
	 *
	 * @param int $id id.
	 * @return \TrustPayments\Sdk\Model\LabelDescriptor
	 */
	public function find( $id ) {
		return parent::find( $id );
	}

	/**
	 * Returns a list of label descriptors.
	 *
	 * @return \TrustPayments\Sdk\Model\LabelDescriptor[]
	 */
	public function get_all() {
		return parent::get_all();
	}

	/**
	 * Fetch data.
	 *
	 * @return array|\TrustPayments\Sdk\Model\LabelDescriptor[]
	 * @throws \TrustPayments\Sdk\ApiException ApiException.
	 * @throws \TrustPayments\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \TrustPayments\Sdk\VersioningException VersioningException.
	 */
	protected function fetch_data() {
		$label_description_service = new \TrustPayments\Sdk\Service\LabelDescriptionService( WC_TrustPayments_Helper::instance()->get_api_client() );
		return $label_description_service->all();
	}

	/**
	 * Get Id.
	 *
	 * @param mixed $entry entry.
	 * @return int|string
	 */
	protected function get_id( $entry ) {
		/* @var \TrustPayments\Sdk\Model\LabelDescriptor $entry */
		return $entry->getId();
	}
}
