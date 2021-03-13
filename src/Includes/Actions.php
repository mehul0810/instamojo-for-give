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
    exit;
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
            $current_url    = ! empty( $data['post_data']['give-current-url'] ) ? $data['post_data']['give-current-url'] : false;
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
            ];
            
            $response      = Instamojo::create_payment_request( $args );
            $response_body = wp_remote_retrieve_body( $response );
            $response_code = wp_remote_retrieve_response_code( $response );
            
            if ( 200 === $response_code ) {
                echo '<pre>'; print_r($response_body); echo '</pre>';
            } else {
                echo '<pre>'; print_r(json_decode($response_body)); echo '</pre>';
                // give_set_error();
            }
            die();
        }

    }

    /**
	 * Add Support for Phone Field.
	 *
	 * @param int $form_id Donation Form ID.
	 *
	 * @since  1.0.2
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
}