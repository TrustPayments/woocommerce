<?php
if (!defined('ABSPATH')) {
	exit();
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
 * Adds TrustPayments settings to WooCommerce Settings Tabs
 */
class WC_TrustPayments_Admin_Settings_Page extends WC_Settings_Page {

	/**
	 * Adds Hooks to output and save settings
	 */
	public function __construct(){
		$this->id = 'trustpayments';
		$this->label = 'Trust Payments';
		
		add_filter('woocommerce_settings_tabs_array', array(
			$this,
			'add_settings_page' 
		), 20);
		add_action('woocommerce_settings_' . $this->id, array(
			$this,
			'settings_tab' 
		));
		add_action('woocommerce_settings_save_' . $this->id, array(
			$this,
			'save' 
		));
		
		add_action('woocommerce_update_options_' . $this->id, array(
			$this,
			'update_settings' 
		));
		
		add_action('woocommerce_admin_field_trustpayments_links', array(
		    $this,
	  		'output_links'
		));
	}

	public function add_settings_tab($settings_tabs){
		$settings_tabs[$this->id] = 'Trust Payments';
		return $settings_tabs;
	}

	public function settings_tab(){
		woocommerce_admin_fields($this->get_settings());
	}
	
	public function save(){
		$settings = $this->get_settings();
		WC_Admin_Settings::save_fields( $settings );
		
	}

	public function update_settings(){
	    WC_TrustPayments_Helper::instance()->reset_api_client();
	    $user_id = get_option(WooCommerce_TrustPayments::CK_APP_USER_ID);
	    $user_key = get_option(WooCommerce_TrustPayments::CK_APP_USER_KEY);
		if (!(empty($user_id) || empty($user_key))) {
            $errorMessage = '';
		    try{
		        WC_TrustPayments_Service_Method_Configuration::instance()->synchronize();
		    }
		    catch (\Exception $e) {
		        WooCommerce_TrustPayments::instance()->log($e->getMessage(), WC_Log_Levels::ERROR);
		        WooCommerce_TrustPayments::instance()->log($e->getTraceAsString(), WC_Log_Levels::DEBUG);
                $errorMessage = __('Could not update payment method configuration.', 'woo-trustpayments');
                WC_Admin_Settings::add_error($errorMessage);
		    }
		    try{
		        WC_TrustPayments_Service_Webhook::instance()->install();
		    }
		    catch (\Exception $e) {
		        WooCommerce_TrustPayments::instance()->log($e->getMessage(), WC_Log_Levels::ERROR);
		        WooCommerce_TrustPayments::instance()->log($e->getTraceAsString(), WC_Log_Levels::DEBUG);
                $errorMessage = __('Could not install webhooks, please check if the feature is active in your space.', 'woo-trustpayments');
                WC_Admin_Settings::add_error($errorMessage);
		    }
		    try{
		        WC_TrustPayments_Service_Manual_Task::instance()->update();
		    }
		    catch (\Exception $e) {
		        WooCommerce_TrustPayments::instance()->log($e->getMessage(), WC_Log_Levels::ERROR);
		        WooCommerce_TrustPayments::instance()->log($e->getTraceAsString(), WC_Log_Levels::DEBUG);
                $errorMessage = __('Could not update the manual task list.', 'woo-trustpayments');
                WC_Admin_Settings::add_error($errorMessage);
		    }
		    try {
		        do_action('wc_trustpayments_settings_changed');
		    }
		    catch (\Exception $e) {
		        WooCommerce_TrustPayments::instance()->log($e->getMessage(), WC_Log_Levels::ERROR);
		        WooCommerce_TrustPayments::instance()->log($e->getTraceAsString(), WC_Log_Levels::DEBUG);
                $errorMessage = $e->getMessage();
                WC_Admin_Settings::add_error($errorMessage);
		    }

            if ( wc_tax_enabled() && ('yes' === get_option( 'woocommerce_tax_round_at_subtotal' ))){
                if('yes' === get_option( WooCommerce_TrustPayments::CK_ENFORCE_CONSISTENCY )) {
                    $errorMessage = __("'WooCommerce > Settings > TrustPayments > Enforce Consistency' and 'WooCommerce > Settings > Tax > Rounding' are both enabled. Please disable at least one of them.", 'woo-trustpayments');
                    WC_Admin_Settings::add_error($errorMessage);
                    WooCommerce_TrustPayments::instance()->log($errorMessage, WC_Log_Levels::ERROR);
                }
            }
		    
		    
		    if(!empty($errorMessage)){
                $errorMessage = __('Please check your credentials and grant the application user the necessary rights (Account Admin) for your space.', 'woo-trustpayments');
		        WC_Admin_Settings::add_error($errorMessage);
		    }			
		    WC_TrustPayments_Helper::instance()->delete_provider_transients();
		}
		
	}
	
	public function output_links($value){
	    foreach($value['links'] as $url => $text){
	        echo '<a href="'.esc_url($url).'" class="page-title-action">'.esc_html($text).'</a>';
	    }
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings(){
	    
		$settings = array(
		    array(
		        'links' => array(
		            'https://plugin-documentation.ep.trustpayments.com/TrustPayments/woocommerce/1.7.22/docs/en/documentation.html' => __('Documentation', 'woo-trustpayments'),
		            'https://ep.trustpayments.com/user/signup' => __('Sign Up', 'woo-trustpayments')
		        ),
		        'type' => 'trustpayments_links',
		    ),
		    
			array(
				'title' => __('General Settings', 'woo-trustpayments'),
			    'desc' => 
			        __('Enter your application user credentials and space id, if you don\'t have an account already sign up above.',
			            'woo-trustpayments'),
				'type' => 'title',
				'id' => 'general_options' 
			),
		    
		    array(
		        'title' => __('Space Id', 'woo-trustpayments'),
		        'id' => WooCommerce_TrustPayments::CK_SPACE_ID,
		        'type' => 'text',
		        'css' => 'min-width:300px;',
		        'desc' => __('(required)', 'woo-trustpayments')
		    ),
			
			array(
				'title' => __('User Id', 'woo-trustpayments'),
				'desc_tip' => __('The user needs to have full permissions in the space this shop is linked to.', 'woo-trustpayments'),
			    'id' => WooCommerce_TrustPayments::CK_APP_USER_ID,
				'type' => 'text',
				'css' => 'min-width:300px;',
				'desc' => __('(required)', 'woo-trustpayments') 
			),
			
			array(
				'title' => __('Authentication Key', 'woo-trustpayments'),
			    'id' => WooCommerce_TrustPayments::CK_APP_USER_KEY,
				'type' => 'password',
				'css' => 'min-width:300px;',
				'desc' => __('(required)', 'woo-trustpayments') 
			),
						
			array(
				'type' => 'sectionend',
				'id' => 'general_options' 
			),
			
			array(
				'title' => __('Email Options', 'woo-trustpayments'),
				'type' => 'title',
				'id' => 'email_options' 
			),
			
			array(
				'title' => __('Send Order Email', 'woo-trustpayments'),
				'desc' => __("Send the order email of WooCommerce.", 'woo-trustpayments'),
			    'id' => WooCommerce_TrustPayments::CK_SHOP_EMAIL,
				'type' => 'checkbox',
				'default' => 'yes',
				'css' => 'min-width:300px;' 
			),
			
			array(
				'type' => 'sectionend',
				'id' => 'email_options' 
			),
			
			array(
				'title' => __('Document Options', 'woo-trustpayments'),
				'type' => 'title',
				'id' => 'document_options' 
			),
			
			array(
				'title' => __('Invoice Download', 'woo-trustpayments'),
				'desc' => __("Allow customers to download the invoice.", 'woo-trustpayments'),
			    'id' => WooCommerce_TrustPayments::CK_CUSTOMER_INVOICE,
				'type' => 'checkbox',
				'default' => 'yes',
				'css' => 'min-width:300px;' 
			),
			array(
				'title' => __('Packing Slip Download', 'woo-trustpayments'),
				'desc' => __("Allow customers to download the packing slip.", 'woo-trustpayments'),
			    'id' => WooCommerce_TrustPayments::CK_CUSTOMER_PACKING,
				'type' => 'checkbox',
				'default' => 'yes',
				'css' => 'min-width:300px;' 
			),
			
			array(
				'type' => 'sectionend',
				'id' => 'document_options' 
			) ,
		    
		    array(
		        'title' => __('Space View Options', 'woo-trustpayments'),
		        'type' => 'title',
		        'id' => 'space_view_options'
		    ),
		   
		    array(
		        'title' => __('Space View Id', 'woo-trustpayments'),
		        'desc_tip' => __('The Space View Id allows to control the styling of the payment form and the payment page within the space.', 'woo-trustpayments'),
		        'id' => WooCommerce_TrustPayments::CK_SPACE_VIEW_ID,
		        'type' => 'number',
		        'css' => 'min-width:300px;'
		    ),
		    
		    array(
		        'type' => 'sectionend',
		        'id' => 'space_view_options'
		    ),

            array(
                'title' => __('Integration Options', 'woo-trustpayments'),
                'type' => 'title',
                'id' => 'integration_options'
            ),


            array(
                'title' => __('Integration Type', 'woo-trustpayments'),
                'desc_tip' => __('The integration type controls how the payment form is integrated into the WooCommerce checkout. The Lightbox integration type offers better performance but with a less compelling checkout experience.', 'woo-trustpayments'),
                'id' => WooCommerce_TrustPayments::CK_INTEGRATION,
                'type' => 'select',
                'css' => 'min-width:300px;',
                'default' => WC_TrustPayments_Integration::IFRAME,
                'options' => array(
                    WC_TrustPayments_Integration::IFRAME => $this->format_display_string(__(WC_TrustPayments_Integration::IFRAME, 'woo-trustpayments')),
                    WC_TrustPayments_Integration::LIGHTBOX  => $this->format_display_string(__(WC_TrustPayments_Integration::LIGHTBOX, 'woo-trustpayments')),
                ),
            ),

            array(
                'type' => 'sectionend',
                'id' => 'integration_options'
            ),

            array(
                'title' => __('Line Items Options', 'woo-trustpayments'),
                'type' => 'title',
                'id' => 'line_items_options'
            ),

            array(
                'title' => __('Enforce Consistency', 'woo-trustpayments'),
                'desc' => __("Require that the transaction line items total is matching the order total.", 'woo-trustpayments'),
                'id' => WooCommerce_TrustPayments::CK_ENFORCE_CONSISTENCY,
                'type' => 'checkbox',
                'default' => 'yes',
                'css' => 'min-width:300px;'
            ),

            array(
                'type' => 'sectionend',
                'id' => 'line_items_options'
            ),

            array(
                'title' => __('Reference Options', 'woo-trustpayments'),
                'type' => 'title',
                'id' => 'reference_options'
            ),


            array(
                'title' => __('Order Reference Type', 'woo-trustpayments'),
                'desc_tip' => __('Choose which order reference is sent.', 'woo-trustpayments'),
                'id' => WooCommerce_TrustPayments::CK_ORDER_REFERENCE,
                'type' => 'select',
                'css' => 'min-width:300px;',
                'default' => WC_TrustPayments_Order_Reference::ORDER_ID,
                'options' => array(
                    WC_TrustPayments_Order_Reference::ORDER_ID => $this->format_display_string(__(WC_TrustPayments_Order_Reference::ORDER_ID, 'woo-trustpayments')),
                    WC_TrustPayments_Order_Reference::ORDER_NUMBER  => $this->format_display_string(__(WC_TrustPayments_Order_Reference::ORDER_NUMBER, 'woo-trustpayments')),
                ),
            ),

            array(
                'type' => 'sectionend',
                'id' => 'reference_options'
            ),


        );
		
		return apply_filters('wc_trustpayments_settings', $settings);
	}

	private function format_display_string($display_string){
		return ucwords(str_replace("_", " ", $display_string));
	}
}
