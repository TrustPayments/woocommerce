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
 * Provider of label descriptor group information from the gateway.
 */
class WC_TrustPayments_Provider_Label_Description_Group extends WC_TrustPayments_Provider_Abstract {

	protected function __construct(){
		parent::__construct('wc_trustpayments_label_description_groups');
	}

	/**
	 * Returns the label descriptor group by the given code.
	 *
	 * @param int $id
	 * @return \TrustPayments\Sdk\Model\LabelDescriptorGroup
	 */
	public function find($id){
		return parent::find($id);
	}

	/**
	 * Returns a list of label descriptor groups.
	 *
	 * @return \TrustPayments\Sdk\Model\LabelDescriptorGroup[]
	 */
	public function get_all(){
		return parent::get_all();
	}

	protected function fetch_data(){
	    $label_description_group_service = new \TrustPayments\Sdk\Service\LabelDescriptionGroupService(WC_TrustPayments_Helper::instance()->get_api_client());
		return $label_description_group_service->all();
	}

	protected function get_id($entry){
		/* @var \TrustPayments\Sdk\Model\LabelDescriptorGroup $entry */
		return $entry->getId();
	}
}