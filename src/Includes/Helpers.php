<?php
/**
 * Instamojo for Give | Helpers.
 *
 * @package WordPress
 * @subpackage Instamojo for Give
 * @since 1.0.0
 */

namespace MG\Instamojo\GiveWP\Includes;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    wp_die( 'Cheating Huh?' );
}

class Helpers {
    /**
     * Get API Endpoint.
     *
     * @since  1.0.0
     * @access public
     *
     * @return void
     */
    public static function get_api_endpoint() {
        $version = apply_filters( 'instamojo_for_give_api_version' , '1.1' );
        $url     = "https://instamojo.com/api/{$version}/";

        if ( give_is_test_mode() ) {
            $url = "https://test.instamojo.com/api/{$version}/";
        }

        return $url;
    }

    /**
     * Get Private API Key.
     *
     * @since  1.0.0
     * @access public
     *
     * @return string
     */
    public static function get_private_api_key() {
        $key = give_get_option( 'instamojo_get_live_api_key' );

        if ( give_is_test_mode() ) {
            $key = give_get_option( 'instamojo_get_test_api_key' );
        }

        return trim( $key );
    }

    /**
     * Get Private Auth Token.
     *
     * @since  1.0.0
     * @access public
     *
     * @return string
     */
    public static function get_private_auth_token() {
        $token = give_get_option( 'instamojo_get_live_auth_token' );

        if ( give_is_test_mode() ) {
            $token = give_get_option( 'instamojo_get_test_auth_token' );
        }

        return trim( $token );
    }

    /**
     * Get Private Salt.
     *
     * @since  1.0.0
     * @access public
     *
     * @return string
     */
    public static function get_private_salt() {
        $salt = give_get_option( 'instamojo_get_live_salt' );

        if ( give_is_test_mode() ) {
            $salt = give_get_option( 'instamojo_get_test_salt' );
        }

        return trim( $salt );
    }

    /**
     * Get Headers.
     *
     * @since  1.0.0
     * @access public
     *
     * @return array
     */
    public static function get_headers() {
        return [
            'X-Api-Key'    => self::get_private_api_key(),
            'X-Auth-Token' => self::get_private_auth_token(),
        ];
    }

    /**
     * Retrieve the donation ID based on the key
     *
     * @param string $key   The key to search for.
     * @param string $value The value to match the key with.
     *
     * @since  1.0.0
     * @access public
     *
     * @return int
     */
    public static function get_donation_id_by_meta( $key, $value ) {
        global $wpdb;

        $meta_table = __give_v20_bc_table_details( 'payment' );

        $result = $wpdb->get_var(
            $wpdb->prepare(
                "
                    SELECT {$meta_table['column']['id']}
                    FROM {$meta_table['name']}
                    WHERE meta_key = '{$key}'
                    AND meta_value = %s
                    ORDER BY {$meta_table['column']['id']} DESC
                    LIMIT 1
                    ",
                $value
            )
        );

        if ( $result != null ) {
            return $result;
        }

        return 0;
    }
}
