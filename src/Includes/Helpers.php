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
}