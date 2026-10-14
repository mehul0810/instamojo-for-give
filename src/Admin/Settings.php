<?php
/**
 * Instamojo for Give | Admin Settings.
 *
 * @package WordPress
 * @subpackage Instamojo for Give
 * @since 1.0.0
 */

namespace MG\Instamojo\GiveWP\Admin;

use MG\Instamojo\GiveWP\Includes\Helpers as Helpers;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	wp_die( 'Cheating Huh?' );
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
		add_action( 'admin_notices', [ $this, 'legacy_api_notice' ] );
		add_action( 'give_admin_field_cta_notice', [ $this, 'render_cta_notice' ], 10, 2 );
		add_filter( 'give_get_sections_gateways', [ $this, 'register_sections' ] );
		add_filter( 'give_get_settings_gateways', [ $this, 'register_settings' ] );
	}

	/** Show migration guidance only on this gateway's settings screen. */
	public function legacy_api_notice() {
		if ( ! isset( $_GET['page'], $_GET['tab'], $_GET['section'] ) || 'give-settings' !== $_GET['page'] || 'gateways' !== $_GET['tab'] || 'instamojo' !== $_GET['section'] ) {
			return;
		}
		if ( '1.1' !== \MG\Instamojo\GiveWP\Includes\ApiSelection::for_new_donation() ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'Instamojo API v1.1 is retained for existing sites and is planned for deprecation in plugin 1.2.0. To migrate new donations, add the v2 OAuth client ID, client secret and webhook salt for each environment you use, then explicitly select API v2 below. Pending v1.1 donations continue to use their original API.', 'instamojo-for-give' ) . '</p></div>';
	}

	/**
	 * Render CTA Notice.
	 *
	 * @param array  $value        List of settings parameters.
	 * @param string $option_value Option value.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return mixed
	 */
	public function render_cta_notice( $value, $option_value ) {
		$wrapper_class = ! empty( $value['wrapper_class'] ) ? 'class="' . $value['wrapper_class'] . '"' : '';
		?>
		<tr valign="top" <?php echo esc_html( $wrapper_class ); ?>>
			<td class="give-cta-notice-wrap" style="padding: 0;" colspan="2">
				<div class="give-cta-notice" style="background-color: #fff; margin-bottom: 20px;">
					<h3 style="text-transform: uppercase; font-size: 14px; font-weight: 700; margin-top: 0; border-bottom: 2px solid #f5f5f5; padding: 12px 20px;">
						<?php esc_attr_e( $value['title'] ); ?>
					</h3>
					<ol style="margin: 10px 35px; font-size: 13px">
						<?php
						foreach ( $value['steps'] as $step ) {
							_e( $step ); // No need for extensive escaping as this array is internally defined.
						}
						?>
					</ol>
					<p class="give-field-description">
						<?php esc_attr_e( $value['desc'] ); ?>
					</p>
				</div>
			</td>
		</tr>
		<?php
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
						'name' => esc_html__( 'API for new donations', 'instamojo-for-give' ),
						'desc' => esc_html__( 'Existing sites with configured v1.1 credentials stay on v1.1 until you explicitly choose v2. Fresh sites use v2.', 'instamojo-for-give' ),
						'id' => 'mg_instamojo_api_version',
						'type' => 'select',
						'default' => \MG\Instamojo\GiveWP\Includes\ApiSelection::for_new_donation(),
						'options' => [ '1.1' => 'API v1.1', 'v2' => 'API v2' ],
					],
					[
						'type'  => 'cta_notice',
						'id'    => 'give_title_gateway_settings_instamojo_start',
						'name'  => esc_html__( 'Need Help?', 'instamojo-for-give' ),
						'desc'  => '',
						'steps' => [
							sprintf(
								'<li><strong>%1$s</strong> <a href="%2$s">%3$s</a> %4$s</li>',
								esc_html__( 'Don\'t have an Instamojo Account?', 'instamojo-for-give' ),
								esc_url_raw( 'https://mehulgohil.com/recommends/instamojo' ),
								esc_html__( 'Click here', 'instamojo-for-give' ),
								esc_html__( 'to create one.', 'instamojo-for-give' )
							),
							sprintf(
								'<li><strong>%1$s</strong> <a href="%2$s">%3$s</a> %4$s</li>',
								esc_html__( 'Existing Instamojo Customer?', 'instamojo-for-give' ),
								esc_url_raw( 'mailto:hello@mehulgohil.com' ),
								esc_html__( 'Email me', 'instamojo-for-give' ),
								esc_html__( 'the email address of your Instmojo account for additional Instamojo account related help.', 'instamojo-for-give' )
							),
							sprintf(
								'<li><strong>%1$s</strong> <a href="%2$s">%3$s</a> %4$s</li>',
								esc_html__( 'Facing an issue with the plugin?', 'instamojo-for-give' ),
								esc_url_raw( 'https://wordpress.org/support/plugin/mg-instamojo-for-give/' ),
								esc_html__( 'Click here', 'instamojo-for-give' ),
								esc_html__( 'to create a ticket on WordPress support forums.', 'instamojo-for-give' )
							),
						],
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
						'name' => esc_html__( 'v2 Live - OAuth Client ID', 'instamojo-for-give' ),
						'id' => 'mg_instamojo_v2_live_client_id',
						'type' => 'text',
					],
					[
						'name' => esc_html__( 'v2 Live - OAuth Client Secret', 'instamojo-for-give' ),
						'id' => 'mg_instamojo_v2_live_client_secret',
						'type' => 'text',
					],
					[
						'name' => esc_html__( 'v2 Live - Webhook Salt', 'instamojo-for-give' ),
						'id' => 'mg_instamojo_v2_live_webhook_salt',
						'type' => 'text',
					],
					[
						'name' => esc_html__( 'v2 Test - OAuth Client ID', 'instamojo-for-give' ),
						'id' => 'mg_instamojo_v2_test_client_id',
						'type' => 'text',
					],
					[
						'name' => esc_html__( 'v2 Test - OAuth Client Secret', 'instamojo-for-give' ),
						'id' => 'mg_instamojo_v2_test_client_secret',
						'type' => 'text',
					],
					[
						'name' => esc_html__( 'v2 Test - Webhook Salt', 'instamojo-for-give' ),
						'id' => 'mg_instamojo_v2_test_webhook_salt',
						'type' => 'text',
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
