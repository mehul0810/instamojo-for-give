<?php
/**
 * Instamojo for Give | Frontend Filters.
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

class Filters {
    /**
     * Constructor.
     * 
     * @since  1.0.0
     * @access public
     */
    public function __construct() {
        add_filter( 'give_payment_gateways', [ $this, 'register_gateways' ] );
    }

    /**
	 * Register Gateways.
	 *
	 * @param array $gateways List of gateways.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return array
	 */
	public function register_gateways( $gateways ) {
		$gateways['instamojo_checkout'] = [
			'admin_label'    => esc_html__( 'Instamojo - Checkout', 'instamojo-for-give' ),
			'checkout_label' => esc_html__( 'Instamojo', 'instamojo-for-give' ),
		];

		return $gateways;
	}
}