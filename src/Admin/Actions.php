<?php
/**
 * Instamojo for Give | Admin Actions.
 *
 * @package WordPress
 * @subpackage Instamojo for Give
 * @since 1.0.0
 */

namespace Instamojo\GiveWP\Admin;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    wp_die( 'Cheating Huh?' );
}

class Actions {
    /**
     * Constructor.
     *
     * @since  1.0.0
     * @access public
     */
    public function __construct() {
        add_action( 'give_payment_view_details', [ $this, 'show_phone_field' ] );
    }

    /**
	 * Show Phone Field under Donation Details.
	 *
	 * @param int $donationId Donation ID.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return mixed
	 */
	public function show_phone_field( $donationId ) {
		$phone = give_get_meta( $donationId, 'instamojo_for_give_phone', true );
		?>
		<div class="column">
			<p>
				<strong>
					<?php esc_html_e( 'Phone:', 'instamojo-for-give' ); ?>
				</strong><br/>
				<?php ! empty( $phone ) ? $phone : esc_html_e( 'N/A', 'instamojo-for-give' ); ?>
			</p>
		</div>
		<?php
	}
}
