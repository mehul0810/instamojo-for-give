<?php
/**
 * Instamojo for Give | Frontend Actions.
 *
 * @package WordPress
 * @subpackage Instamojo for Give
 * @since 1.0.0
 */

namespace Instamojo\GiveWP\Includes;

use Instamojo\GiveWP\Includes\Instamojo as Instamojo;

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
        add_action( 'give_gateway_instamojo_checkout', [ $this, 'process_donation' ], 99 );
		add_action( 'give_instamojo_checkout_cc_form', '__return_false' );
        add_action( 'give_donation_form_after_email', [ $this, 'add_phone_field' ] );
        add_filter( 'give_donation_form_required_fields', [ $this, 'require_phone_field' ], 10, 2 );
        add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
        add_action( 'init', [ $this, 'listen_to_response' ] );
    }

    /**
     * Process Donation.
     *
     * @param array $data List of posted data.
     *
     * @since  1.0.0
     * @access public
     *
     * @return void
     */
    public function process_donation( $data ) {
        // Check for any stored errors.
        $errors = give_get_errors();

        if ( ! $errors ) {
            $formId         = ! empty( $data['post_data']['give-form-id'] ) ? intval( $data['post_data']['give-form-id'] ) : false;
            $donorPhone     = ! empty( $data['post_data']['give_phone'] ) ? $data['post_data']['give_phone'] : '';
            $currency       = give_get_currency( $formId );
            $donorEmail     = $data['user_email'];
            $purchaseKey    = $data['purchase_key'];
            $donationAmount = $data['price'];
            $userInfo       = $data['user_info'];
            $donorName      = ! empty( $userInfo['last_name'] ) ? "{$userInfo['first_name']} {$userInfo['last_name']}" : $userInfo['first_name'];
            $formTitle      = $data['post_data']['give-form-title'];
            $gateway        = $data['gateway'];

            // Setup the donation details which need to send to PayFast.
            $data_to_send = [
                'price'           => $donationAmount,
                'give_form_title' => $formTitle,
                'give_form_id'    => $formId,
                'give_price_id'   => isset( $data['post_data']['give-price-id'] ) ? $data['post_data']['give-price-id'] : '',
                'date'            => $data['date'],
                'user_email'      => $donorEmail,
                'purchase_key'    => $purchaseKey,
                'currency'        => $currency,
                'user_info'       => $userInfo,
                'status'          => 'pending',
                'gateway'         => $gateway,
            ];

            // Record the pending payment.
            $donation_id = give_insert_payment( $data_to_send );

            // Verify donation payment.
            if ( ! $donation_id ) {
                // Record the error.
                give_record_gateway_error(
                    esc_html__( 'Donation Error', 'instamojo-for-give' ),
                    sprintf(
                        /* translators: %s: donation data */
                        esc_html__( 'Donation creation failed before processing payment via Instamojo. Donation data: %s', 'instamojo-for-give' ),
                        wp_json_encode( $data )
                    ),
                    $donation_id
                );

                // Problems? Send back.
                give_send_back_to_checkout( '?payment-mode=' . $data['post_data']['payment-mode'] );
            }

            $args = [
                'amount'       => give_maybe_sanitize_amount( $donationAmount ),
                'purpose'      => "Donation for {$formTitle}",
                'buyer_name'   => $donorName,
                'email'        => $donorEmail,
                'phone'        => $donorPhone,
                'redirect_url' => add_query_arg(
                    [
                        'listener' => 'instamojo_checkout',
                    ],
                    give_get_success_page_uri()
                ),
            ];

            $response      = Instamojo::create_payment_request( $args );
            $response_body = json_decode( wp_remote_retrieve_body( $response ) );
            $response_code = json_decode( wp_remote_retrieve_response_code( $response ) );

            if ( 201 === $response_code && $response_body->success ) {
                give_update_meta( $donation_id, 'instamojo_for_give_payment_request_id', $response_body->payment_request->id );

                // Send donor to Instamojo Checkout page.
                wp_redirect( $response_body->payment_request->longurl );
            } else {
				$message_details = array_values( (array) $response_body->message );
				$message_content = $message_details[0][0];

				// Set error message to show on donation form.
                give_set_error( 'invalid-response', $message_content[0][0] );

				// Update donation status to `Abandoned`.
				give_update_payment_status( $donation_id, 'abandoned' );

				// Set Donation Note.
				give_insert_payment_note( $donation_id, "Donation automatically abandoned due to error: {$message_content[0][0]}" );

				// Problems? Send back.
                give_send_back_to_checkout( '?payment-mode=' . $data['post_data']['payment-mode'] );
            }

			// Don't proceed further.
            give_die();
        }

    }

    /**
	 * Add Support for Phone Field.
	 *
	 * @param int $form_id Donation Form ID.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_phone_field( $form_id ) {
		// Bailout, if Instamojo Checkout is not the selected payment gateway.
		if ( 'instamojo_checkout' !== give_get_chosen_gateway( $form_id ) ) {
			return;
		}

        ?>
        <p id="give-phone-wrap" class="form-row form-row-wide">
            <label class="give-label" for="give-phone">
                <?php esc_html_e( 'Phone Number', 'instamojo-for-give' ); ?>
                <span class="give-required-indicator">*</span>
                <span class="give-tooltip give-icon give-icon-question" data-tooltip="<?php esc_attr_e( 'Please enter your phone number.', 'instamojo-for-give' ); ?>"></span>
            </label>
            <input
                class="give-input required"
                type="text"
                name="give_phone"
                placeholder="<?php esc_attr_e( 'Phone Number', 'instamojo-for-give' ); ?>"
                id="give-phone"
                value=""
            />
        </p>
        <?php
	}

    /**
     * Require Phone Field.
     *
     * Adds error validation to the phone field.
     *
     * @param array $required_fields List of required fields.
     * @param int   $form_id         Donation Form ID.
     *
     * @since  1.0.0
     * @access public
     *
     * @return array
     */
    public static function require_phone_field( $required_fields, $form_id ) {
        // Bailout, if Instamojo Checkout is not the selected payment gateway.
        if ( 'instamojo_checkout' !== give_get_chosen_gateway( $form_id ) ) {
            return;
        }

        $required_fields['give_phone'] = [
            'error_id'      => 'invalid_phone',
            'error_message' => esc_html__( 'Please enter your phone number.', 'instamojo-for-give' ),
        ];

        return $required_fields;
    }

    /**
     * Register Assets.
     *
     * @since  1.0.0
     * @access public
     *
     * @return void
     */
    public function register_assets() {
        // Bailout, if the `Instamojo Checkout` gateway is not active.
        if ( ! give_is_gateway_active( 'instamojo_checkout' ) ) {
            return;
        }

        wp_enqueue_script( 'instamojo-checkout', 'https://js.instamojo.com/v1/checkout.js', '', INSTAMOJO_FOR_GIVE_VERSION );
    }

    /**
     * Listen to response.
     *
     * @since  1.0.0
     * @access public
     *
     * @return void
     */
    public function listen_to_response() {
        $get_data = give_clean( $_GET );

        // Bailout, if the listener is not from Instamojo Checkout.
        if (
			! isset( $get_data['listener'] ) ||
			(
				! empty( $get_data['listener'] ) &&
				'instamojo_checkout' !== $get_data['listener']
			)
		) {
            return;
        }

        $payment_request_id = $get_data['payment_request_id'];
        $transaction_id     = $get_data['payment_id'];
        $donation_id        = Helpers::get_donation_id_by_meta( 'instamojo_for_give_payment_request_id', $payment_request_id );

        $response      = Instamojo::get_payment_details( $payment_request_id, $transaction_id );
        $response_body = json_decode( wp_remote_retrieve_body( $response ) );
        $response_code = json_decode( wp_remote_retrieve_response_code( $response ) );

        if ( 200 === $response_code && 'Completed' === $response_body->payment_request->status ) {
            // Update donation status to `Complete`.
			give_update_payment_status( $donation_id, 'publish' );

			// Link `Transaction ID` to the donation.
            give_set_payment_transaction_id( $donation_id, $transaction_id );

			// Send donor to `Donation Confirmation` page.
			give_send_to_success_page();
        } else {
			// Update donation status to `Failed`.
            give_update_payment_status( $donation_id, 'failed' );

			// Set Donation Note.
			give_insert_payment_note( $donation_id, esc_html__( 'Donation didn\'t go through as the payment with Instamojo has failed.') );

			// Redirect to `Donation Failed` page as payment didn't go through.
            wp_redirect( give_get_failed_transaction_uri() );
        }
    }
}
