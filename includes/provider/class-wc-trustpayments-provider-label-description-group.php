<?php
/**
 *
 * WC_TrustPayments_Provider_Label_Description_Group Class
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
 * Provider of label descriptor group information from the gateway.
 */
class WC_TrustPayments_Provider_Label_Description_Group extends WC_TrustPayments_Provider_Abstract {

	/**
	 * Construct.
	 */
	protected function __construct() {
		parent::__construct( 'wc_trustpayments_label_description_groups' );
	}

	/**
	 * Returns the label descriptor group by the given code.
	 *
	 * @param int $id Id.
	 * @return \TrustPayments\Sdk\Model\LabelDescriptorGroup
	 */
	public function find( $id ) {
		return parent::find( $id );
	}

	/**
	 * Returns a list of label descriptor groups.
	 *
	 * @return \TrustPayments\Sdk\Model\LabelDescriptorGroup[]
	 */
	public function get_all() {
		return parent::get_all();
	}

	/**
	 * Fetch data.
	 *
	 * @return array|\TrustPayments\Sdk\Model\LabelDescriptorGroup[]
	 * @throws \TrustPayments\Sdk\ApiException ApiException.
	 * @throws \TrustPayments\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \TrustPayments\Sdk\VersioningException VersioningException.
	 */
	protected function fetch_data() {
		$label_description_group_service = new \TrustPayments\Sdk\Service\LabelDescriptionGroupService( WC_TrustPayments_Helper::instance()->get_api_client() );
		return $label_description_group_service->all();
	}

	/**
	 * Get id.
	 *
	 * @param mixed $entry entry.
	 * @return int|string
	 */
	protected function get_id( $entry ) {
		/* @var \TrustPayments\Sdk\Model\LabelDescriptorGroup $entry */
		return $entry->getId();
	}
}
