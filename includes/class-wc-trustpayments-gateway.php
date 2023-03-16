<?php
/**
 *
 * WC_TrustPayments_Gateway Class
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
 * Class WC_TrustPayments_Gateway.
 *
 * @class WC_TrustPayments_Gateway
 */
/**
 * This class implements the Trust Payments gateways
 */
class WC_TrustPayments_Gateway extends WC_Payment_Gateway {
	/**
	 * Payment method configuration id.
	 *
	 * @var $payment_method_configuration_id payment method configuration id.
	 */
	private $payment_method_configuration_id;
	/**
	 * Contains a users saved tokens for this gateway.
	 *
	 * @var $tokens tokens.
	 */
	protected $tokens = array();
	/**
	 * We prefix out private variables as other plugins do strange things.
	 *
	 * @var $tru_payment_method_configuration_id tru payment method configuration id.
	 */
	private $tru_payment_method_configuration_id;
	/**
	 * The tru payment method cofiguration
	 *
	 * @var $tru_payment_method_configuration tru payment method cofiguration.
	 */
	private $tru_payment_method_configuration = null;
	/**
	 * The tru translated title
	 *
	 * @var $tru_translated_title tru translated title.
	 */
	private $tru_translated_title = null;
	/**
	 * The tru translated description
	 *
	 * @var $tru_translated_description tru translated description.
	 */
	private $tru_translated_description = null;
	/**
	 * Show description?
	 *
	 * @var $tru_show_description tru show description?
	 */
	private $tru_show_description = 'yes';
	/**
	 * Show icon?
	 *
	 * @var $tru_show_icon tru show icon?
	 */
	private $tru_show_icon = 'yes';
	/**
	 * Image
	 *
	 * @var tru_image tru image.
	 */
	private $tru_image = null;

	/**
	 * Constructor.
	 *
	 * @param WC_TrustPayments_Entity_Method_Configuration $method configuration method.
	 */
	public function __construct( WC_TrustPayments_Entity_Method_Configuration $method ) {
		$this->tru_payment_method_configuration_id = $method->get_id();
		$this->id = 'trustpayments_' . $method->get_id();
		$this->has_fields = false;
		$this->method_title = $method->get_configuration_name();
		$this->method_description = WC_TrustPayments_Helper::instance()->translate( $method->get_description() );
		$this->tru_image = $method->get_image();
		$this->tru_image_base = $method->get_image_base();
		$this->icon = WC_TrustPayments_Helper::instance()->get_resource_url( $this->tru_image, $this->tru_image_base );

		// We set the title and description here, as some plugin access the variables directly.
		$this->title = $method->get_configuration_name();
		$this->description = '';

		$this->tru_translated_title = $method->get_title();
		$this->tru_translated_description = $method->get_description();

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->enabled = $this->get_option( 'enabled' );
		$this->tru_show_description = $this->get_option( 'show_description' );
		$this->tru_show_icon = $this->get_option( 'show_icon' );

		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array(
				$this,
				'process_admin_options',
			)
		);

		$this->supports = array(
			'products',
			'refunds',
		);
	}

	/**
	 * Returns the payment method configuration.
	 *
	 * @return WC_TrustPayments_Entity_Method_Configuration
	 */
	public function get_payment_method_configuration() {
		if ( is_null( $this->tru_payment_method_configuration ) ) {
			$this->tru_payment_method_configuration = WC_TrustPayments_Entity_Method_Configuration::load_by_id(
				$this->tru_payment_method_configuration_id
			);
		}
		return $this->tru_payment_method_configuration;
	}

	/**
	 * Return the gateway's title fontend.
	 *
	 * @return string
	 */
	public function get_title() {
		$title = $this->title;
		$translated = WC_TrustPayments_Helper::instance()->translate( $this->tru_translated_title );
		if ( ! is_null( $translated ) ) {
			$title = $translated;
		}
		return apply_filters( 'woocommerce_gateway_title', $title, $this->id );
	}

	/**
	 * Return the gateway's description frontend.
	 *
	 * @return string
	 */
	public function get_description() {
		$description = '';
		if ( 'yes' == $this->tru_show_description ) {
			$translated = WC_TrustPayments_Helper::instance()->translate( $this->tru_translated_description );
			if ( ! is_null( $translated ) ) {
				$description = $translated;
			}
		}
		return apply_filters( 'woocommerce_gateway_description', $description, $this->id );
	}

	/**
	 * Return the gateway's icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		$icon = '';
		if ( 'yes' == $this->tru_show_icon ) {
			$space_id = $this->get_payment_method_configuration()->get_space_id();
			$space_view_id = get_option( WooCommerce_TrustPayments::CK_SPACE_VIEW_ID );
			$language = WC_TrustPayments_Helper::instance()->get_cleaned_locale();

			$url = WC_TrustPayments_Helper::instance()->get_resource_url( $this->tru_image_base, $this->tru_image, $language, $space_id, $space_view_id );
			$icon = '<img src="' . WC_HTTPS::force_https_url( $url ) . '" alt="' . esc_attr( $this->get_title() ) . '" width="35px" />';
		}

		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title' => __( 'Enable/Disable', 'woocommerce' ),
				'type' => 'checkbox',
				/* translators: %s: method title */
				'label' => sprintf( __( 'Enable %s', 'woo-trustpayments' ), $this->method_title ),
				'default' => 'yes',
			),
			'title_value' => array(
				'title' => __( 'Title', 'woocommerce' ),
				'type' => 'info',
				'value' => $this->get_title(),
				'description' => __( 'This controls the title which the user sees during checkout.', 'woo-trustpayments' ),
			),
			'description_value' => array(
				'title' => __( 'Description', 'woocommerce' ),
				'type' => 'info',
				'value' => ! empty( $this->get_description() ) ? esc_attr( $this->get_description() ) : __( '[not set]', 'woo-trustpayments' ),
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woo-trustpayments' ),
			),
			'show_description' => array(
				'title' => __( 'Show description', 'woo-trustpayments' ),
				'type' => 'checkbox',
				'label' => __( 'Yes', 'woo-trustpayments' ),
				'default' => 'yes',
				'description' => __( "Show the payment method's description on the checkout page.", 'woo-trustpayments' ),
				'desc_tip' => true,
			),
			'show_icon' => array(
				'title' => __( 'Show method image', 'woo-trustpayments' ),
				'type' => 'checkbox',
				'label' => __( 'Yes', 'woo-trustpayments' ),
				'default' => 'yes',
				'description' => __( "Show the payment method's image on the checkout page.", 'woo-trustpayments' ),
				'desc_tip' => true,
			),
		);
	}

	/**
	 * Generate info HTML.
	 *
	 * @param  mixed $key key.
	 * @param  mixed $data data.
	 * @return string
	 */
	public function generate_info_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults = array(
			'title' => '',
			'class' => '',
			'css' => '',
			'placeholder' => '',
			'desc_tip' => true,
			'description' => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
<tr valign="top">
	<th scope="row" class="titledesc">
                            <?php // phpcs:ignore ?>
							<?php echo $this->get_tooltip_html( $data ); ?>
							<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<legend class="screen-reader-text">
				<span><?php echo wp_kses_post( $data['title'] ); ?></span>
			</legend>
			<div class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo esc_html( $this->get_custom_attribute_html( $data ) ); ?> >
								<?php echo esc_html( $data['value'] ); ?>
						</div>
		</fieldset>
	</td>
</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Validate Info Field.
	 *
	 * @param  string      $key Field key++.
	 * @param  string|null $value Posted Value.
	 * @return void
	 */
	public function validate_info_field( $key, $value ) {
		return;
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * @return bool
	 */
	public function is_available() {
		$is_available = parent::is_available();

		if ( ! $is_available ) {
			return false;
		}

		// It's not possible to support the rounding on subtotal level and still get valid tax rates and amounts.
		// Therefore the payment methods are disabled, if this option is active.
		if ( wc_tax_enabled() && ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) ) {
			if ( 'yes' === get_option( WooCommerce_TrustPayments::CK_ENFORCE_CONSISTENCY ) ) {
				$error_message = __( "'WooCommerce > Settings > TrustPayments > Enforce Consistency' and 'WooCommerce > Settings > Tax > Rounding' are both enabled. Please disable at least one of them.", 'woo-trustpayments' );
				WooCommerce_TrustPayments::instance()->log( $error_message, WC_Log_Levels::ERROR );
				return false;
			}
		}

		// It is possbile this function is called in the wordpress admin section. There is not a cart, so all active methods are available.
		// If it is not a checkout page the method is availalbe. Some plugins check this, on non checkout pages, without a cart available.
		// The active  gateways are  available during order total caluclation, as other plugins could need them.
		if ( apply_filters( 'wc_trustpayments_is_method_available', is_admin() || ! is_checkout() || ( isset( $GLOBALS['_wc_trustpayments_calculating'] ) && $GLOBALS['_wc_trustpayments_calculating'] ), $this ) ) {
			return $this->get_payment_method_configuration()->get_state() == WC_TrustPayments_Entity_Method_Configuration::STATE_ACTIVE;
		}

		if ( apply_filters( 'wc_trustpayments_is_order_pay_endpoint', is_checkout_pay_page() ) ) {
			// We have to use the order and not the cart for this endpoint.
			global $wp;
			$order = WC_Order_Factory::get_order( $wp->query_vars['order-pay'] );
			if ( ! $order ) {
				return false;
			}
			try {
				$possible_methods = WC_TrustPayments_Service_Transaction::instance()->get_possible_payment_methods_for_order( $order );
			} catch ( WC_TrustPayments_Exception_Invalid_Transaction_Amount $e ) {
				WooCommerce_TrustPayments::instance()->log( $e->getMessage() . ' Order Id: ' . $order->get_id(), WC_Log_Levels::ERROR );
				return false;
			} catch ( Exception $e ) {
				WooCommerce_TrustPayments::instance()->log( $e->getMessage(), WC_Log_Levels::DEBUG );
				return false;
			}
		} else {
			try {
				$possible_methods = WC_TrustPayments_Service_Transaction::instance()->get_possible_payment_methods_for_cart();
			} catch ( WC_TrustPayments_Exception_Invalid_Transaction_Amount $e ) {
				WooCommerce_TrustPayments::instance()->log( $e->getMessage(), WC_Log_Levels::ERROR );
				return false;
			} catch ( \TrustPayments\Sdk\ApiException $e ) {
				WooCommerce_TrustPayments::instance()->log( $e->getMessage(), WC_Log_Levels::DEBUG );
				$response_object = $e->getResponseObject();
				$is_client_error = ( $response_object instanceof \TrustPayments\Sdk\Model\ClientError );
				if ( $is_client_error ) {
					$error_types = array( 'CONFIGURATION_ERROR', 'DEVELOPER_ERROR' );
					if ( in_array( $response_object->getType(), $error_types ) ) {
						$message = esc_attr( $response_object->getType() ) . ': ' . esc_attr( $response_object->getMessage() );
						wc_print_notice( $message, 'error' );
						return false;
					}
				}
				return false;
			} catch ( Exception $e ) {
				WooCommerce_TrustPayments::instance()->log( $e->getMessage(), WC_Log_Levels::DEBUG );
				return false;
			}
		}

		$possible = false;
		foreach ( $possible_methods as $possible_method ) {
			if ( $possible_method == $this->get_payment_method_configuration()->get_configuration_id() ) {
				$possible = true;
				break;
			}
		}
		if ( ! $possible ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the gateway has fields on the checkout.
	 *
	 * @return bool
	 */
	public function has_fields() {
		return true;
	}

	/**
	 * Load payment fields.
	 *
	 * @return bool
	 */
	public function payment_fields() {

		parent::payment_fields();
		$transaction_service = WC_TrustPayments_Service_Transaction::instance();
		$woocommerce_data = get_plugin_data( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php', false, false );
		try {
			if ( apply_filters( 'wc_trustpayments_is_order_pay_endpoint', is_checkout_pay_page() ) ) {
				global $wp;
				$order = WC_Order_Factory::get_order( $wp->query_vars['order-pay'] );
				if ( ! $order ) {
					return false;
				}
				$transaction = $transaction_service->get_transaction_from_order( $order );
			} else {
				$transaction = $transaction_service->get_transaction_from_session();
			}
			if ( ! wp_script_is( 'trustpayments-remote-checkout-js', 'enqueued' ) ) {
				$ajax_url = '';
				if ( get_option( WooCommerce_TrustPayments::CK_INTEGRATION ) == WC_TrustPayments_Integration::LIGHTBOX
                    || version_compare( $woocommerce_data['Version'], WooCommerce_TrustPayments::WC_MAXIMUM_VERSION, '>' ) ) {
					$ajax_url = $transaction_service->get_lightbox_url_for_transaction( $transaction );
				} else {
					$ajax_url = $transaction_service->get_javascript_url_for_transaction( $transaction );
				}
				wp_enqueue_script(
					'trustpayments-remote-checkout-js',
					$ajax_url,
					array(
						'jquery',
					),
					1,
					true
				);
				wp_enqueue_script(
					'trustpayments-checkout-js',
					WooCommerce_TrustPayments::instance()->plugin_url() . '/assets/js/frontend/checkout.js',
					array(
						'jquery',
						'jquery-blockui',
						'trustpayments-remote-checkout-js',
					),
					1,
					true
				);
				$localize = array(
					'i18n_not_complete' => __( 'Please fill out all required fields.', 'woo-trustpayments' ),
					'integration' => get_option( WooCommerce_TrustPayments::CK_INTEGRATION ),
				);
				if ( version_compare( $woocommerce_data['Version'], WooCommerce_TrustPayments::WC_MAXIMUM_VERSION, '>' ) ) {
					$localize['integration'] = get_option( WC_TrustPayments_Integration::LIGHTBOX );
				}
				wp_localize_script( 'trustpayments-checkout-js', 'trustpayments_js_params', $localize );
				wp_add_inline_script( 'trustpayments-checkout-js', 'window.onload = function () {  wc_trustpayments_checkout.init(); };' );

			}
			$transaction_nonce = hash_hmac( 'sha256', $transaction->getLinkedSpaceId() . '-' . $transaction->getId(), NONCE_KEY );

			?>
		
			<div id="payment-form-<?php echo esc_attr( $this->id ); ?>">
				<input type="hidden" id="trustpayments-iframe-possible-<?php echo esc_attr( ( $this->id ) ); ?>" name="trustpayments-iframe-possible-<?php echo esc_attr( $this->id ); ?>" value="false" />
			</div>
			<input type="hidden" id="trustpayments-space-id-<?php echo esc_attr( $this->id ); ?>" name="trustpayments-space-id-<?php echo esc_attr( $this->id ); ?>" value="<?php echo esc_attr( $transaction->getLinkedSpaceId() ); ?>"  />
			<input type="hidden" id="trustpayments-transaction-id-<?php echo esc_attr( $this->id ); ?>" name="trustpayments-transaction-id-<?php echo esc_attr( $this->id ); ?>" value="<?php echo esc_attr( $transaction->getId() ); ?>"  />
			<input type="hidden" id="trustpayments-transaction-nonce-<?php echo esc_attr( $this->id ); ?>" name="trustpayments-transaction-nonce-<?php echo esc_attr( $this->id ); ?>" value="<?php echo esc_attr( $transaction_nonce ); ?>" />
			<div id="trustpayments-method-configuration-<?php echo esc_attr( $this->id ); ?>"
				class="trustpayments-method-configuration" style="display: none;"
				data-method-id="<?php echo esc_attr( $this->id ); ?>"
				data-configuration-id="<?php echo esc_attr( $this->get_payment_method_configuration()->get_configuration_id() ); ?>"
				data-container-id="payment-form-<?php echo esc_attr( $this->id ); ?>" data-description-available="<?php var_export( ! empty( $this->get_description() ) ); ?>"></div>
			<?php

		} catch ( Exception $e ) {
			WooCommerce_TrustPayments::instance()->log( $e->getMessage(), WC_Log_Levels::DEBUG );
		}

	}

	/**
	 * Validate frontend fields.
	 *
	 * @return bool
	 */
	public function validate_fields() {
		return true;
	}

	/**
	 * Process Payment.
	 *
	 * @param int $order_id order id.
	 * @return array
	 */
	public function process_payment( $order_id ) {

		if ( isset( $_POST[ 'trustpayments-space-id-' . $this->id ] ) ) {
			$space_id = sanitize_text_field( wp_unslash( $_POST[ 'trustpayments-space-id-' . $this->id ] ) );
		} else {
			$space_id = '';
		}
		if ( isset( $_POST[ 'trustpayments-transaction-id-' . $this->id ] ) ) {
			$transaction_id = sanitize_text_field( wp_unslash( $_POST[ 'trustpayments-transaction-id-' . $this->id ] ) );
		} else {
			$transaction_id = '';
		}
		if ( isset( $_POST[ 'trustpayments-transaction-nonce-' . $this->id ] ) ) {
			$transaction_nonce = sanitize_text_field( wp_unslash( $_POST[ 'trustpayments-transaction-nonce-' . $this->id ] ) );
		} else {
			$transaction_nonce = '';
		}

		$is_order_pay_endpoint = apply_filters( 'wc_trustpayments_is_order_pay_endpoint', is_checkout_pay_page() );

		if ( hash_hmac( 'sha256', $space_id . '-' . $transaction_id, NONCE_KEY ) != $transaction_nonce ) {
			WC()->session->set( 'trustpayments_failure_message', __( 'The checkout timed out, please try again.', 'woo-trustpayments' ) );
			WC()->session->set( 'reload_checkout', true );
			return array(
				'result' => 'failure',
			);
		}

		$existing = WC_TrustPayments_Entity_Transaction_Info::load_by_order_id( $order_id );
		if ( $existing->get_id() ) {
			if ( $existing->get_space_id() != $space_id && $existing->get_transaction_id() != $transaction_id ) {
				WC()->session->set( 'trustpayments_failure_message', __( 'There was an issue, while processing your order. Please try again or use another payment method.', 'woo-trustpayments' ) );
				WC()->session->set( 'reload_checkout', true );
				return array(
					'result' => 'failure',
				);
			}
		}

		$order = wc_get_order( $order_id );
		$sanitized_post_data = wp_verify_nonce( isset( $_POST[ 'trustpayments-iframe-possible-' . $this->id ] ) );
		$no_iframe = isset( $sanitized_post_data ) && 'false' == $sanitized_post_data;

		try {
			$transaction_service = WC_TrustPayments_Service_Transaction::instance();
			$transaction = $transaction_service->get_transaction( $space_id, $transaction_id );

			$order->add_meta_data( '_trustpayments_pay_for_order', $is_order_pay_endpoint, true );
			$order->add_meta_data( '_trustpayments_gateway_id', $this->id, true );
			$order->delete_meta_data( '_trustpayments_confirmed' );
			$order->save();

			if ( $transaction->getState() == \TrustPayments\Sdk\Model\TransactionState::PENDING ) {
				$transaction = $transaction_service->confirm_transaction( $transaction_id, $space_id, $order, $this->get_payment_method_configuration()->get_configuration_id() );
				$transaction_service->update_transaction_info( $transaction, $order );
			}

			WC()->session->set( 'order_awaiting_payment', false );
			WC_TrustPayments_Helper::instance()->destroy_current_cart_id();
			WC()->session->set( 'trustpayments_space_id', null );
			WC()->session->set( 'trustpayments_transaction_id', null );

			$result = array(
				'result' => 'success',
				'trustpayments' => 'true',
			);

			$woocommerce_data = get_plugin_data( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php', false, false );
			if ( version_compare( $woocommerce_data['Version'], WooCommerce_TrustPayments::WC_MAXIMUM_VERSION, '>' ) ) {
				$result['redirect'] = $transaction_service->get_payment_page_url( $transaction->getLinkedSpaceId(), $transaction->getId() );
			}

			if ( $no_iframe ) {
				$result = array(
					'result' => 'success',
					'redirect' => $transaction_service->get_payment_page_url( $transaction->getLinkedSpaceId(), $transaction->getId() ),
				);
				return $result;
			}

			if ( apply_filters( 'wc_trustpayments_gateway_result_send_json', $is_order_pay_endpoint, $order_id ) ) {
				wp_send_json( $result );
				exit;
			} else {
				return $result;
			}
		} catch ( Exception $e ) {
			$message = $e->getMessage();
			$cleaned = preg_replace( '/^\[[A-Fa-f\d\-]+\] /', '', $message );
			WC()->session->set( 'trustpayments_failure_message', $cleaned );
			$order->update_status( 'failed' );
			$result = array(
				'result' => 'failure',
				'reload' => 'true',
			);
			if ( apply_filters( 'wc_trustpayments_gateway_result_send_json', $is_order_pay_endpoint, $order_id ) ) {
				wp_send_json( $result );
				exit;
			}
			WC()->session->set( 'reload_checkout', true );
			return array(
				'result' => 'failure',
			);
		}
	}

	/**
	 * Process refund.
	 *
	 * If the gateway declares 'refunds' support, this will allow it to refund.
	 * a passed in amount.
	 *
	 * @param  int    $order_id order id.
	 * @param  float  $amount amount.
	 * @param  string $reason reason.
	 * @return boolean True or false based on success, or a WP_Error object.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		if ( ! isset( $GLOBALS['trustpayments_refund_id'] ) ) {
			return new WP_Error( 'trustpayments_error', __( 'There was a problem creating the refund.', 'woo-trustpayments' ) );
		}
		$refund = WC_Order_Factory::get_order( $GLOBALS['trustpayments_refund_id'] );
		$order = WC_Order_Factory::get_order( $order_id );

		try {
			WC_TrustPayments_Admin_Refund::execute_refund( $order, $refund );
		} catch ( Exception $e ) {
			return new WP_Error( 'trustpayments_error', $e->getMessage() );
		}

		$refund_job_id = $refund->get_meta( '_trustpayments_refund_job_id', true );

		$wait = 0;
		while ( $wait < 5 ) {
			$refund_job = WC_TrustPayments_Entity_Refund_Job::load_by_id( $refund_job_id );
			if ( $refund_job->get_state() == WC_TrustPayments_Entity_Refund_Job::STATE_FAILURE ) {
				return new WP_Error( 'trustpayments_error', $refund_job->get_failure_reason() );
			} elseif ( $refund_job->get_state() == WC_TrustPayments_Entity_Refund_Job::STATE_SUCCESS ) {
				return true;
			}
			$wait++;
			sleep( 1 );
		}
		return true;
	}
}
