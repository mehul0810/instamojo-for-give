<?php
/**
 * Instamojo for Give | Frontend Actions.
 *
 * @package WordPress
 * @subpackage Instamojo for Give
 * @since 1.0.0
 */

namespace MG\Instamojo\GiveWP\Includes;

use MG\Instamojo\GiveWP\Includes\Instamojo as Instamojo;

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
            if ( 'INR' !== strtoupper( (string) $currency ) ) {
                give_set_error( 'instamojo-currency', esc_html__( 'Instamojo donations require INR currency.', 'instamojo-for-give' ) );
                give_send_back_to_checkout( '?payment-mode=' . $data['post_data']['payment-mode'] );
                return;
            }
            $version = ApiSelection::for_new_donation();
            $environment = give_is_test_mode() ? 'test' : 'live';
            $client = new ApiClient( $version, $environment );
            if ( ! $client->is_configured() ) {
                give_set_error( 'instamojo-credentials', esc_html__( 'Instamojo credentials are incomplete for the selected API and environment.', 'instamojo-for-give' ) );
                give_send_back_to_checkout( '?payment-mode=' . $data['post_data']['payment-mode'] );
                return;
            }
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

            give_update_meta( $donation_id, 'MG_instamojo_for_give_api_version', $version );
            give_update_meta( $donation_id, 'MG_instamojo_for_give_environment', $environment );
            $args = [
                'amount'       => give_maybe_sanitize_amount( $donationAmount ),
                'purpose'      => "Donation for {$formTitle}",
                'buyer_name'   => $donorName,
                'email'        => $donorEmail,
                'phone'        => $donorPhone,
                'allow_repeated_payments' => false,
                'redirect_url' => add_query_arg( [ 'listener' => 'instamojo_checkout' ], give_get_success_page_uri() ),
                'webhook'      => add_query_arg( [ 'listener' => 'instamojo_checkout_webhook' ], home_url( '/' ) ),
            ];
            give_update_meta( $donation_id, 'MG_instamojo_for_give_account_fingerprint', $client->account_fingerprint() );
            $response = $client->create( $args );
            $request = $client->parse_create( $response );
            if ( $request ) {
                give_update_meta( $donation_id, 'MG_instamojo_for_give_payment_request_id', $request['id'] );
                wp_redirect( $request['url'] );
            } else {
                // An ambiguous provider response must not mark a possibly payable donation failed.
                give_set_error( 'invalid-response', esc_html__( 'Could not confirm an Instamojo payment request. Please contact the site administrator before trying again.', 'instamojo-for-give' ) );
                give_insert_payment_note( $donation_id, 'Instamojo payment request was not confirmed; manual review may be needed.' );
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
            return $required_fields;
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

        wp_enqueue_script( 'instamojo-checkout', 'https://js.instamojo.com/v1/checkout.js', '', MG_INSTAMOJO_FOR_GIVE_VERSION );
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
        $listener = isset( $_GET['listener'] ) ? (string) $_GET['listener'] : '';
        if ( 'instamojo_checkout' === $listener ) {
            $this->handle_return();
        } elseif ( 'instamojo_checkout_webhook' === $listener && isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
            $this->handle_webhook();
        }
    }

    private function handle_return() {
        $request_id = isset( $_GET['payment_request_id'] ) ? (string) $_GET['payment_request_id'] : '';
        $payment_id = isset( $_GET['payment_id'] ) ? (string) $_GET['payment_id'] : '';
        $result = $this->reconcile( $request_id, $payment_id );
        if ( 'complete' === $result ) {
            give_send_to_success_page();
        } elseif ( 'failed' === $result ) {
            wp_redirect( give_get_failed_transaction_uri() );
        } else {
            // Keep an unverified donation pending and give the donor an accurate result.
            wp_die( esc_html__( 'Your Instamojo payment is pending verification. Please contact the site administrator before attempting another donation.', 'instamojo-for-give' ) );
        }
    }

    private function handle_webhook() {
        // MAC is defined over the original submitted values, before sanitization.
        $data = wp_unslash( $_POST );
        if ( ! is_array( $data ) || ! isset( $data['payment_request_id'], $data['payment_id'], $data['mac'] ) || ! is_scalar( $data['payment_request_id'] ) || ! is_scalar( $data['payment_id'] ) ) {
            wp_die( '', '', array( 'response' => 400 ) );
        }
        $request_id = (string) $data['payment_request_id'];
        $donation_id = Helpers::get_donation_id_by_meta( 'MG_instamojo_for_give_payment_request_id', $request_id );
        if ( ! $donation_id || 'instamojo_checkout' !== give_get_payment_gateway( $donation_id ) ) {
            wp_die( '', '', array( 'response' => 404 ) );
        }
        $version = ApiSelection::for_donation( $donation_id );
        $environment = ApiSelection::environment_for_donation( $donation_id );
        if ( '' === $environment ) {
            wp_die( '', '', array( 'response' => 503 ) );
        }
        $key = 'v2' === $version ? 'mg_instamojo_v2_' . $environment . '_webhook_salt' : 'instamojo_get_' . $environment . '_salt';
        $salt = trim( (string) give_get_option( $key ) );
        if ( '' === $salt || ! is_string( $data['mac'] ) ) {
            wp_die( '', '', array( 'response' => 503 ) );
        }
        $mac = $data['mac'];
        unset( $data['mac'] );
        if ( ! self::verify_webhook_mac( $data, $mac, $salt ) ) {
            wp_die( '', '', array( 'response' => 403 ) );
        }
        $result = $this->reconcile( $request_id, (string) $data['payment_id'], $donation_id );
        wp_die( '', '', array( 'response' => '' === $result ? 503 : 200 ) );
    }

    public static function verify_webhook_mac( $data, $mac, $salt ) {
        if ( ! is_array( $data ) || ! is_string( $mac ) || '' === $salt ) {
            return false;
        }
        uksort( $data, 'strcasecmp' );
        foreach ( $data as $value ) {
            if ( ! is_scalar( $value ) ) {
                return false;
            }
        }
        return hash_equals( hash_hmac( 'sha1', implode( '|', array_values( $data ) ), $salt ), $mac );
    }

    /** Verify against the stored API and donation before mutating payment state. */
    private function reconcile( $request_id, $payment_id, $donation_id = 0 ) {
        if ( '' === $request_id || '' === $payment_id || ! preg_match( '/^[A-Za-z0-9_-]+$/', $payment_id ) ) {
            return '';
        }
        if ( ! $donation_id ) {
            $donation_id = Helpers::get_donation_id_by_meta( 'MG_instamojo_for_give_payment_request_id', $request_id );
        }
        if ( ! $donation_id || 'instamojo_checkout' !== give_get_payment_gateway( $donation_id ) ) {
            return '';
        }
        if ( (string) give_get_meta( $donation_id, 'MG_instamojo_for_give_payment_request_id', true ) !== $request_id ) {
            return '';
        }
        $environment = ApiSelection::environment_for_donation( $donation_id );
        if ( '' === $environment ) {
            return '';
        }
        $version = ApiSelection::for_donation( $donation_id );
        $client = new ApiClient( $version, $environment );
        $saved_fingerprint = (string) give_get_meta( $donation_id, 'MG_instamojo_for_give_account_fingerprint', true );
        if ( '' !== $saved_fingerprint && ! hash_equals( $saved_fingerprint, $client->account_fingerprint() ) ) {
            return '';
        }
        $payment = $client->parse_payment( $client->payment( $payment_id ) );
        if ( ! $payment ) {
            return '';
        }
        $id_key = 'v2' === $version ? 'id' : 'payment_id';
        $provider_id = isset( $payment[ $id_key ] ) ? (string) $payment[ $id_key ] : '';
        $provider_request = isset( $payment['payment_request'] ) ? $payment['payment_request'] : '';
        if ( is_array( $provider_request ) ) {
            $provider_request = isset( $provider_request['id'] ) ? $provider_request['id'] : '';
        }
        if ( is_string( $provider_request ) && false !== strpos( $provider_request, '/' ) ) {
            $provider_request = basename( rtrim( $provider_request, '/' ) );
        }
        if ( $provider_id !== $payment_id || (string) $provider_request !== $request_id || ! isset( $payment['amount'], $payment['currency'] ) || ! is_numeric( $payment['amount'] ) || ! is_string( $payment['currency'] ) ) {
            return '';
        }
        $expected_amount = (float) give_get_payment_amount( $donation_id );
        if ( $expected_amount <= 0 || abs( (float) $payment['amount'] - $expected_amount ) > 0.001 || strtoupper( (string) $payment['currency'] ) !== strtoupper( (string) give_get_payment_currency_code( $donation_id ) ) ) {
            return '';
        }
        $current = give_get_payment_status( $donation_id );
        $existing_transaction = (string) give_get_meta( $donation_id, '_give_payment_transaction_id', true );
        if ( '' !== $existing_transaction && $existing_transaction !== $payment_id ) {
            return '';
        }
        $transaction_owners = give_get_payments( array(
            'number' => 2,
            'status' => 'any',
            'output' => 'posts',
            'meta_query' => array( array( 'key' => '_give_payment_transaction_id', 'value' => $payment_id, 'compare' => '=' ) ),
        ) );
        foreach ( $transaction_owners as $owner ) {
            if ( isset( $owner->ID ) && (int) $owner->ID !== (int) $donation_id ) {
                return '';
            }
        }
        $status = isset( $payment['status'] ) ? $payment['status'] : null;
        $success = 'v2' === $version ? true === $status : 'Credit' === $status;
        $failure = 'v2' === $version ? false === $status : 'Failed' === $status;
        if ( $success ) {
            if ( 'publish' !== $current && 'complete' !== $current ) {
                if ( 'pending' !== $current && 'failed' !== $current ) {
                    return '';
                }
                give_set_payment_transaction_id( $donation_id, $payment_id );
                give_update_payment_status( $donation_id, 'publish' );
            }
            return 'complete';
        }
        if ( $failure ) {
            if ( 'publish' === $current || 'complete' === $current ) {
                return 'complete';
            }
            if ( 'pending' === $current ) {
                give_update_payment_status( $donation_id, 'failed' );
            }
            return 'failed';
        }
        return '';
    }
}
