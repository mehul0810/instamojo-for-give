<?php
/**
 * Instamojo for Give | Admin Settings.
 *
 * @package WordPress
 * @subpackage Instamojo for Give
 * @since 1.0.0
 */

namespace Instamojo\GiveWP\Admin;

use Instamojo\GiveWP\Includes\Helpers as Helpers;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {
	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function __construct() {
		add_filter( 'give_get_sections_gateways', [ $this, 'register_sections' ] );
		add_filter( 'give_get_settings_gateways', [ $this, 'register_settings' ] );
	}

	/**
	 * Register Admin Sections.
	 *
	 * @param array $sections List of sections.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return array
	 */
	public function register_sections( $sections ) {
		$sections['instamojo'] = esc_html__( 'Instamojo', 'instamojo-for-give' );

		return $sections;
	}

	/**
	 * Register Admin Settings.
	 *
	 * @param array $settings List of settings.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return array
	 */
	public function register_settings( $settings ) {
		$current_section = give_get_current_setting_section();

		switch ( $current_section ) {
			case 'instamojo':
				$settings = [
					[
						'type' => 'title',
						'id'   => 'give_title_gateway_settings_instamojo_start',
					],
					[
						'name'    => esc_html__( 'Live - Private API Key', 'instamojo-for-give' ),
						'desc'    => esc_html__( 'Please enter the private API key from your LIVE Instamojo Account.', 'instamojo-for-give' ),
						'id'      => 'instamojo_get_live_api_key',
						'type'    => 'text',
						'default' => '',
					],
                    [
						'name'    => esc_html__( 'Live - Private Auth Token', 'instamojo-for-give' ),
						'desc'    => esc_html__( 'Please enter the private Authentication Token from your LIVE Instamojo Account.', 'instamojo-for-give' ),
						'id'      => 'instamojo_get_live_auth_token',
						'type'    => 'text',
						'default' => '',
					],
                    [
						'name'    => esc_html__( 'Live - Private Salt', 'instamojo-for-give' ),
						'desc'    => esc_html__( 'Please enter the private Salt from your LIVE Instamojo Account.', 'instamojo-for-give' ),
						'id'      => 'instamojo_get_live_salt',
						'type'    => 'text',
						'default' => '',
					],
                    [
						'name'    => esc_html__( 'Test - Private API Key', 'instamojo-for-give' ),
						'desc'    => esc_html__( 'Please enter the private API key from your TEST Instamojo Account.', 'instamojo-for-give' ),
						'id'      => 'instamojo_get_test_api_key',
						'type'    => 'text',
						'default' => '',
					],
                    [
						'name'    => esc_html__( 'Test - Private Auth Token', 'instamojo-for-give' ),
						'desc'    => esc_html__( 'Please enter the private Authentication Token from your TEST Instamojo Account.', 'instamojo-for-give' ),
						'id'      => 'instamojo_get_test_auth_token',
						'type'    => 'text',
						'default' => '',
					],
                    [
						'name'    => esc_html__( 'Test - Private Salt', 'instamojo-for-give' ),
						'desc'    => esc_html__( 'Please enter the private Salt from your TEST Instamojo Account.', 'instamojo-for-give' ),
						'id'      => 'instamojo_get_test_salt',
						'type'    => 'text',
						'default' => '',
					],
					[
						'name'    => esc_html__( 'Collect Billing Address', 'instamojo-for-give' ),
						'desc'    => esc_html__( 'You can collect the billing address of each donation by enabling this option.', 'instamojo-for-give' ),
						'id'      => 'instamojo_enable_billing_address',
						'type'    => 'radio_inline',
						'default' => 'enabled',
						'options' => [
							'enabled'  => esc_html__( 'Enabled', 'instamojo-for-give' ),
							'disabled' => esc_html__( 'Disabled', 'instamojo-for-give' ),
						],
					],
                    [
                        'type' => 'sectionend',
                        'id'   => 'give_title_gateway_settings_instamojo_end',
                    ]
				];
				break;
		}
		return $settings;
	}
}
