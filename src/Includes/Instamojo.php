<?php
/**
 * Instamojo for Give | Instamojo API.
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

class Instamojo {
    /**
     * Create Payment Request.
     *
     * @param array $data Data to send.
     * @param string|null $version Explicit API for a stored donation, when known.
     * @param string|null $environment Explicit stored environment, when known.
     *
     * @since  1.0.0
     * @access public
     *
     * @return object
     */
    public static function create_payment_request( $data, $version = null, $environment = null ) {
        $version = null === $version ? ApiSelection::for_new_donation() : $version;
        $environment = null === $environment ? ( give_is_test_mode() ? 'test' : 'live' ) : $environment;
        $client = new ApiClient( $version, $environment );
        return $client->create( $data );
    }

    /**
     * Get Payment Details.
     *
     * @param string $payment_request_id Payment Request ID.
     * @param string $payment_id Payment ID.
     * @param string|null $version Explicit API for a stored donation, when known.
     * @param string|null $environment Explicit stored environment, when known.
     *
     * @since  1.0.0
     * @access public
     *
     * @return object
     */
    public static function get_payment_details( $payment_request_id, $payment_id, $version = null, $environment = null ) {
        $version = null === $version ? ApiSelection::for_new_donation() : $version;
        $environment = null === $environment ? ( give_is_test_mode() ? 'test' : 'live' ) : $environment;
        $client = new ApiClient( $version, $environment );
        return $client->payment( $payment_id );
    }
}
