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
?>
<div class="error notice notice-error">
	<p><?php _e( 'The Trust Payments payment methods are not available, if the taxes are rounded at subtotal level. Please disable the \'Round tax at subtotal level, instead of rounding per line\' in the tax settings to enable the Trust Payments payment methods.', 'woo-trustpayments' ); ?></p>
</div>