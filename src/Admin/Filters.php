<?php
/**
 * Instamojo for Give | Admin Filters.
 *
 * @package WordPress
 * @subpackage Instamojo for Give
 * @since 1.0.0
 */

namespace MG\Instamojo\GiveWP\Admin;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    wp_die( 'Cheating Huh?' );
}

class Filters {
    /**
     * Constructor.
     *
     * @since  1.0.0
     * @access public
     */
    public function __construct() {
        add_filter( 'plugin_action_links_' . MG_INSTAMOJO_FOR_GIVE_PLUGIN_BASENAME, [ $this, 'add_plugin_action_links' ] );
    }

    /**
	 * Plugin page action links.
	 *
	 * @param array $actions An array of plugin action links.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function add_plugin_action_links( $actions ) {
		$new_actions = [
			'settings' => sprintf(
				'<a href="%1$s">%2$s</a>',
				admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=gateways&section=instamojo' ),
				esc_html__( 'Settings', 'instamojo-for-give' )
			),
			'support'  => sprintf(
				'<a target="_blank" href="%1$s">%2$s</a>',
				esc_url_raw( 'https://wordpress.org/support/plugin/instamojo-for-give/' ),
				esc_html__( 'Support', 'instamojo-for-give' )
			),
		];

		return array_merge( $new_actions, $actions );
	}
}
