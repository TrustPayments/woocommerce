<?php
/**
 *
 * WC_TrustPayments_Order_Reference Class
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
 * Class WC_TrustPayments_Order_Reference.
 *
 * @class WC_TrustPayments_Order_Reference
 */
/**
 * This class handles the database setup and migration.
 */
class WC_TrustPayments_Order_Reference {
	const ORDER_ID = 'order_id';
	const ORDER_NUMBER = 'order_number';
}
