<?php
/**
 * Instamojo for Give | Frontend Actions.
 * 
 * @package WordPress
 * @subpackage Instamojo for Give
 * @since 1.0.0
 */

namespace Instamojo\GiveWP\Includes;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
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
}