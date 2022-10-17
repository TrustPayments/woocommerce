<?php
/**
 *
 * WC_TrustPayments_Cron Class
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
 * Class WC_TrustPayments_Cron.
 *
 * @class WC_TrustPayments_Cron
 */
/**
 * This class handles the cron jobs
 */
class WC_TrustPayments_Cron {

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action(
			'cron_schedules',
			array(
				__CLASS__,
				'add_custom_cron_schedule',
			),
			5
		);
	}

	/**
	 * Add cron schedule.
	 *
	 * @param  array $schedules schedules.
	 * @return array
	 */
	public static function add_custom_cron_schedule( $schedules ) {
		$schedules['five_minutes'] = array(
			'interval' => 300,
			'display'  => __( 'Every Five Minutes' ),
		);
		return $schedules;
	}

	/**
	 * Activate the cron.
	 */
	public static function activate() {
		if ( ! wp_next_scheduled( 'trustpayments_five_minutes_cron' ) ) {
			wp_schedule_event( time(), 'five_minutes', 'trustpayments_five_minutes_cron' );
		}
	}

	/**
	 * Deactivate the cron.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'trustpayments_five_minutes_cron' );
	}
}
WC_TrustPayments_Cron::init();
