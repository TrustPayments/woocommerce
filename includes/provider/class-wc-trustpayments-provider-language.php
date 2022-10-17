<?php
/**
 *
 * WC_TrustPayments_Provider_Language Class
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
 * Provider of language information from the gateway.
 */
class WC_TrustPayments_Provider_Language extends WC_TrustPayments_Provider_Abstract {

	/**
	 * Construct.
	 */
	protected function __construct() {
		parent::__construct( 'wc_trustpayments_languages' );
	}

	/**
	 * Returns the language by the given code.
	 *
	 * @param string $code code.
	 * @return \TrustPayments\Sdk\Model\RestLanguage
	 */
	public function find( $code ) {
		return parent::find( $code );
	}

	/**
	 * Returns the primary language in the given group.
	 *
	 * @param string $code code.
	 * @return \TrustPayments\Sdk\Model\RestLanguage
	 */
	public function find_primary( $code ) {
		$code = substr( $code, 0, 2 );
		foreach ( $this->get_all() as $language ) {
			if ( $language->getIso2Code() == $code && $language->getPrimaryOfGroup() ) {
				return $language;
			}
		}

		return false;
	}

	/**
	 * Find by iso code.
	 *
	 * @param mixed $iso iso.
	 * @return false|\TrustPayments\Sdk\Model\RestLanguage
	 */
	public function find_by_iso_code( $iso ) {
		foreach ( $this->get_all() as $language ) {
			if ( $language->getIso2Code() == $iso || $language->getIso3Code() == $iso ) {
				return $language;
			}
		}
		return false;
	}

	/**
	 * Returns a list of language.
	 *
	 * @return \TrustPayments\Sdk\Model\RestLanguage[]
	 */
	public function get_all() {
		return parent::get_all();
	}

	/**
	 * Fetch data.
	 *
	 * @return array|\TrustPayments\Sdk\Model\RestLanguage[]
	 * @throws \TrustPayments\Sdk\ApiException ApiException.
	 * @throws \TrustPayments\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \TrustPayments\Sdk\VersioningException VersioningException.
	 */
	protected function fetch_data() {
		$language_service = new \TrustPayments\Sdk\Service\LanguageService( WC_TrustPayments_Helper::instance()->get_api_client() );
		return $language_service->all();
	}

	/**
	 * Get id.
	 *
	 * @param mixed $entry entry.
	 * @return string
	 */
	protected function get_id( $entry ) {
		/* @var \TrustPayments\Sdk\Model\RestLanguage $entry */
		return $entry->getIetfCode();
	}
}
