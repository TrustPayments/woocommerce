<?php
/**
 *
 * WC_TrustPayments_Autoloader Class
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
 * Class WC_TrustPayments_Autoloader.
 *
 * @class WC_TrustPayments_Autoloader
 */
/**
 * This is the autoloader for Trust Payments classes.
 */
class WC_TrustPayments_Autoloader {

	/**
	 * Path to the includes directory.
	 *
	 * @var string
	 */
	private $include_path = '';

	/**
	 * The Constructor.
	 */
	public function __construct() {
		spl_autoload_register(
			array(
				$this,
				'autoload',
			)
		);
		$this->include_path = WC_TRUSTPAYMENTS_ABSPATH . 'includes/';
	}

	/**
	 * Take a class name and turn it into a file name.
	 *
	 * @param  string $class class.
	 * @return string
	 */
	private function get_file_name_from_class( $class ) {
		return 'class-' . str_replace( '_', '-', $class ) . '.php';
	}

	/**
	 * Include a class file.
	 *
	 * @param  string $path path.
	 * @return bool successful or not
	 */
	private function load_file( $path ) {
		if ( $path && is_readable( $path ) ) {
			include_once $path;
			return true;
		}
		return false;
	}

	/**
	 * Auto-load WC TrustPayments classes on demand to reduce memory consumption.
	 *
	 * @param string $class class.
	 */
	public function autoload( $class ) {
		$class = strtolower( $class );

		if ( 0 !== strpos( $class, 'wc_trustpayments' ) ) {
			return;
		}

		$file = $this->get_file_name_from_class( $class );
		$path = '';

		if ( strpos( $class, 'wc_trustpayments_service' ) === 0 ) {
			$path = $this->include_path . 'service/';
		} elseif ( strpos( $class, 'wc_trustpayments_entity' ) === 0 ) {
			$path = $this->include_path . 'entity/';
		} elseif ( strpos( $class, 'wc_trustpayments_provider' ) === 0 ) {
			$path = $this->include_path . 'provider/';
		} elseif ( strpos( $class, 'wc_trustpayments_webhook' ) === 0 ) {
			$path = $this->include_path . 'webhook/';
		} elseif ( strpos( $class, 'wc_trustpayments_exception' ) === 0 ) {
			$path = $this->include_path . 'exception/';
		} elseif ( strpos( $class, 'wc_trustpayments_admin' ) === 0 ) {
			$path = $this->include_path . 'admin/';
		}

		if ( empty( $path ) || ! $this->load_file( $path . $file ) ) {
			$this->load_file( $this->include_path . $file );
		}

		$this->load_file( $this->include_path . $file );
	}
}

new WC_TrustPayments_Autoloader();
