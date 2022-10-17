<?php
/**
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
?>
<div class="error notice notice-error">
	<p><?php esc_html_e( 'The Trust Payments payment methods are not available, if the taxes are rounded at subtotal level. Please disable the \'Round tax at subtotal level, instead of rounding per line\' in the tax settings to enable the Trust Payments payment methods.', 'woo-trustpayments' ); ?></p>
</div>
