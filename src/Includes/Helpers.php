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
        $version = apply_filters( 'instamojo_for_give_api_version' , 'v2' );
        $url     = "https://api.instamojo.com/{$version}/";
        
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
        $access_token = self::get_private_auth_token();
        return [
            'Authorization' => "Bearer {$access_token}",
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
        if ( ! function_exists( 'give_get_payments' ) || '' === $key || '' === $value ) {
            return 0;
        }

        $payments = give_get_payments(
            [
                'number'     => 1,
                'orderby'    => 'ID',
                'order'      => 'DESC',
                'status'     => 'any',
                'output'     => 'posts',
                'meta_query' => [
                    [
                        'key'     => $key,
                        'value'   => $value,
                        'compare' => '=',
                    ],
                ],
            ]
        );

        return isset( $payments[0]->ID ) ? (int) $payments[0]->ID : 0;
    }
}
