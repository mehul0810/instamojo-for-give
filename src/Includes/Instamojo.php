<?php
/**
 * Instamojo for Give | Instamojo API.
 * 
 * @package WordPress
 * @subpackage Instamojo for Give
 * @since 1.0.0
 */

namespace Instamojo\GiveWP\Includes;

use Instamojo\GiveWP\Includes\Helpers as Helpers;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Instamojo {
    /**
     * Create Payment Request.
     *
     * @param array $data Data to send.
     * 
     * @since  1.0.0
     * @access public
     * 
     * @return object
     */
    public static function create_payment_request( $data ) {
        $url  = Helpers::get_api_endpoint() . 'payment-requests';
        $args = [
            'headers' => Helpers::get_headers(),
            'body'    => $data,
        ];

        return wp_remote_post( $url, $args );
    }

    /**
     * Get Payment Details.
     *
     * @param string $payment_request_id Payment Request ID.
     * @param string $payment_id Payment ID.
     * 
     * @since  1.0.0
     * @access public
     * 
     * @return object
     */
    public static function get_payment_details( $payment_request_id, $payment_id ) {
        $endpoint = Helpers::get_api_endpoint();
        $url      = "{$endpoint}payment-requests/{$payment_request_id}/{$payment_id}";
        $args     = [
            'headers' => Helpers::get_headers(),
        ];

        // Decode JSON encoded response.
        return wp_remote_get( $url, $args );
    }
}