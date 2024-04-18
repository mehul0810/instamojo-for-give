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
     * Get Access Token.
     *
     * @since  1.0.0
     * @access public
     *
     * @return string
     */
    public static function get_access_token() {
        $client_id = give_get_option( 'mg_instamojo_get_client_id' );
        $client_secret = give_get_option( 'mg_instamojo_get_client_secret' );

        if ( give_is_test_mode() ) {
           
        }

        $payload_data = [
            'grant_type' => 'client_credentials',
            'client_id' => $client_id,
            'client_secret' => $client_secret
        ];

        $url  = 'https://api.instamojo.com/oauth2/token/';
        
        $args = [
            'body'    => $payload_data,
        ];
        
        $response     = wp_remote_post( $url, $args );
        $response_body = json_decode( wp_remote_retrieve_body( $response ) );
        $response_code = json_decode( wp_remote_retrieve_response_code( $response ) );
        if(200 === $response_code) {
            return $response_body->access_token;
        } else {
            return '';
        }
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
        //$access_token = self::get_private_auth_token();
        $access_token = self::get_access_token();
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
