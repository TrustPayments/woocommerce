<?php
if (!defined('ABSPATH')) {
	exit();
}
/**
 * Trust Payments WooCommerce
 *
 * This WooCommerce plugin enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author wallee AG (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */
/**
 * Shows the document downloads buttons and handles the downloads in the order overview.
 */
class WC_TrustPayments_Admin_Document {

	public static function init(){
		add_action('add_meta_boxes', array(
			__CLASS__,
			'add_meta_box' 
		), 40);
		add_action('woocommerce_admin_order_actions_end', array(
			__CLASS__,
			'add_buttons_to_overview' 
		), 12, 1);
		add_action('admin_init', array(
			__CLASS__,
			'download_document' 
		));
	}

	public static function add_buttons_to_overview(WC_Order $order){
		$method = wc_get_payment_gateway_by_order($order);
		if (!($method instanceof WC_TrustPayments_Gateway)) {
			return;
		}
		$transaction_info = WC_TrustPayments_Entity_Transaction_Info::load_by_order_id($order->get_id());

		if ($transaction_info->get_id() == null) {
			return;
		}
		if (in_array($transaction_info->get_state(),
				array(
				    \TrustPayments\Sdk\Model\TransactionState::COMPLETED,
				    \TrustPayments\Sdk\Model\TransactionState::FULFILL,
				    \TrustPayments\Sdk\Model\TransactionState::DECLINE 
				))) {
			
			$url = wp_nonce_url(
					add_query_arg(
							array(
								'post' => $order->get_id(),
								'refer' => 'overview',
								'trustpayments_admin' => 'download_invoice' 
							), admin_url('post.php')), 'download_invoice', 'nonce');
			$title = esc_attr(__('Invoice', 'woo-trustpayments'));
			printf('<a class="button tips trustpayments-action-button  trustpayments-button-download-invoice" href="%1s" data-tip="%2s">%2s</a>', $url, $title, $title);
		}
		if ($transaction_info->get_state() == \TrustPayments\Sdk\Model\TransactionState::FULFILL) {
			$url = wp_nonce_url(
					add_query_arg(
							array(
								'post' => $order->get_id(),
								'refer' => 'overview',
								'trustpayments_admin' => 'download_packing' 
							), admin_url('post.php')), 'download_packing', 'nonce');
			$title = esc_attr(__('Packing Slip', 'woo-trustpayments'));
			printf('<a class="button tips trustpayments-action-button trustpayments-button-download-packingslip" href="%1s" data-tip="%2s">%2s</a>', $url, $title, $title);
		}
	}

	/**
	 * Add WC Meta boxes.
	 */
	public static function add_meta_box(){
		global $post;
		if ($post->post_type != 'shop_order') {
			return;
		}
		$order = WC_Order_Factory::get_order($post->ID);
		$method = wc_get_payment_gateway_by_order($order);
		if (!($method instanceof WC_TrustPayments_Gateway)) {
			return;
		}
		$transaction_info = WC_TrustPayments_Entity_Transaction_Info::load_by_order_id($order->get_id());
		if ($transaction_info->get_id() != null && in_array($transaction_info->get_state(),
				array(
				    \TrustPayments\Sdk\Model\TransactionState::COMPLETED,
				    \TrustPayments\Sdk\Model\TransactionState::FULFILL,
				    \TrustPayments\Sdk\Model\TransactionState::DECLINE 
				))) {
			add_meta_box('woocommerce-order-trustpayments-documents', __('Trust Payments Documents', 'woo-trustpayments'), array(
				__CLASS__,
				'output' 
			), 'shop_order', 'side', 'default');
		}
	}

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output($post){
		global $post;
		
		$order = WC_Order_Factory::get_order($post->ID);
		$method = wc_get_payment_gateway_by_order($order);
		if (!($method instanceof WC_TrustPayments_Gateway)) {
			return;
		}
		$transaction_info = WC_TrustPayments_Entity_Transaction_Info::load_by_order_id($order->get_id());
		if ($transaction_info->get_id() == null) {
			return;
		}
		if (in_array($transaction_info->get_state(),
				array(
				    \TrustPayments\Sdk\Model\TransactionState::COMPLETED,
				    \TrustPayments\Sdk\Model\TransactionState::FULFILL,
				    \TrustPayments\Sdk\Model\TransactionState::DECLINE 
				))) {
			
			?>
<ul class="woocommerce-order-admin-trustpayments-downloads">
	<li><a
		href="<?php
			
			echo wp_nonce_url(
					add_query_arg(
							array(
								'post' => $order->get_id(),
								'refer' => 'edit',
								'trustpayments_admin' => 'download_invoice' 
							), admin_url('post.php')), 'download_invoice', 'nonce');
			?>"
		class="trustpayments-admin-download trustpayments-admin-download-invoice button"><?php _e('Invoice', 'woo-trustpayments')?></a></li>				

					<?php if ($transaction_info->get_state() == \TrustPayments\Sdk\Model\TransactionState::FULFILL): ?>
						<li><a
		href="<?php
				
				echo wp_nonce_url(
						add_query_arg(
								array(
									'post' => $order->get_id(),
									'refer' => 'edit',
									'trustpayments_admin' => 'download_packing' 
								), admin_url('post.php')), 'download_packing', 'nonce');
				?>"
		class="trustpayments-admin-download trustpayments-admin-download-packingslip button"><?php _e('Packing Slip', 'woo-trustpayments')?></a></li>
					<?php endif;?>
					</ul>
<?php
		}
	}

	/**
	 * Admin pdf actions callback.
	 * Within admin by default only administrator and shop managers have permission to view, create, cancel invoice.
	 */
	public static function download_document(){
		if (!self::is_download_request()) {
			return;
		}
		
		// sanitize data and verify nonce.
		$action = sanitize_key($_GET['trustpayments_admin']);
		$nonce = sanitize_key($_GET['nonce']);
		if (!wp_verify_nonce($nonce, $action)) {
			wp_die('Invalid request.');
		}
		
		// validate allowed user roles.
		$user = wp_get_current_user();
		$allowed_roles = apply_filters('wc_trustpayments_allowed_roles_to_download_documents', array(
			'administrator',
			'shop_manager' 
		));
		if (!array_intersect($allowed_roles, $user->roles)) {
			wp_die('Access denied');
		}
		
		$order_id = intval($_GET['post']);
		try {
			switch ($action) {
				case 'download_invoice':
				    WC_TrustPayments_Download_Helper::download_invoice($order_id);
					break;
				case 'download_packing':
				    WC_TrustPayments_Download_Helper::download_packing_slip($order_id);
					break;
			}
		}
		catch (Exception $e) {
		    $message = $e->getMessage();
		    $cleaned = preg_replace("/^\[[A-Fa-f\d\-]+\] /", "", $message);
		    wp_die(__('Could not fetch the document from Trust Payments.', 'woo-trustpayments').' '.$cleaned);
		}
		if ($_GET['refer'] == 'edit') {
			wp_redirect(add_query_arg(array(
				'post' => $order_id,
				'action' => 'edit' 
			), admin_url('post.php')));
		}
		else {
			wp_redirect(add_query_arg(array(
				'post_type' => 'shop_order' 
			), admin_url('edit.php')));
		}
		exit();
	}

	/**
	 * Check if request is PDF action.
	 *
	 * @return bool
	 */
	private static function is_download_request(){
		return (isset($_GET['post']) && isset($_GET['trustpayments_admin']) && isset($_GET['nonce']));
	}
}

WC_TrustPayments_Admin_Document::init();
