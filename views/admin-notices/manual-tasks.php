<?php 
if (!defined('ABSPATH')) {
	exit(); // Exit if accessed directly.
}
/**
 * Trust Payments WooCommerce
 *
 * This WooCommerce plugin enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author wallee AG (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */
?>

<div class="error notice notice-error">
	<p><?php
    	if($number_of_manual_tasks == 1){
    	    _e('There is a manual task that needs your attention.', 'woo-trustpayments');
    	}
    	else{
    	   echo  sprintf(_n('There is %s manual task that needs your attention.', 'There are %s manual tasks that need your attention', $number_of_manual_tasks, 'woo-trustpayments'), $number_of_manual_tasks);
    	}
		?>
    	</p>
	<p>
		<a href="<?php echo $manual_taks_url?>" target="_blank"><?php _e('View', 'woo-trustpayments')?></a>
	</p>
</div>