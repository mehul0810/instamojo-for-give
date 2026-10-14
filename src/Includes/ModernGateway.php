<?php
namespace MG\Instamojo\GiveWP\Includes;

use Give\Donations\Models\Donation;
use Give\Framework\PaymentGateways\Commands\RedirectOffsite;
use Give\Framework\PaymentGateways\Exceptions\PaymentGatewayException;
use Give\Framework\PaymentGateways\PaymentGateway;

if ( ! defined( 'ABSPATH' ) ) {
    wp_die( 'Cheating Huh?' );
}

/** GiveWP 3.x Visual Builder adapter. Legacy form processing remains on its original hook. */
class ModernGateway extends PaymentGateway {
    public static function id(): string {
        return 'instamojo_checkout';
    }

    public function getId(): string {
        return self::id();
    }

    public function getName(): string {
        return __( 'Instamojo - Checkout', 'instamojo-for-give' );
    }

    public function getPaymentMethodLabel(): string {
        return __( 'Instamojo', 'instamojo-for-give' );
    }

    public function supportsFormVersions(): array {
        return array( 3 );
    }

    public function supportsRefund(): bool {
        return false;
    }

    public function refundDonation( Donation $donation ) {
        throw new PaymentGatewayException( __( 'Instamojo refunds are not supported by this gateway.', 'instamojo-for-give' ) );
    }

    public function enqueueScript(int $formId) {
        wp_register_script( 'instamojo-give-modern', false, array(), MG_INSTAMOJO_FOR_GIVE_VERSION, true );
        wp_enqueue_script( 'instamojo-give-modern' );
        wp_add_inline_script( 'instamojo-give-modern', 'window.givewp.gateways.register({id:"instamojo_checkout",Fields:function(){return window.React.createElement("span",null,"You will be redirected to Instamojo to complete your donation.");}});' );
    }

    public function createPayment( Donation $donation, $gatewayData ) {
        if ( 'INR' !== strtoupper( (string) $donation->amount->getCurrency()->getCode() ) ) {
            throw new PaymentGatewayException( __( 'Instamojo donations require INR currency.', 'instamojo-for-give' ) );
        }
        $version = ApiSelection::for_new_donation();
        $environment = $donation->mode->isTest() ? 'test' : 'live';
        $client = new ApiClient( $version, $environment );
        if ( ! $client->is_configured() ) {
            throw new PaymentGatewayException( __( 'Instamojo credentials are incomplete for the selected API and environment.', 'instamojo-for-give' ) );
        }
        give_update_meta( $donation->id, 'MG_instamojo_for_give_api_version', $version );
        give_update_meta( $donation->id, 'MG_instamojo_for_give_environment', $environment );
        give_update_meta( $donation->id, 'MG_instamojo_for_give_account_fingerprint', $client->account_fingerprint() );
        $response = $client->create( array(
            'amount' => $donation->amount->formatToDecimal(),
            'purpose' => 'Donation for ' . $donation->formTitle,
            'buyer_name' => trim( $donation->firstName . ' ' . $donation->lastName ),
            'email' => $donation->email,
            'phone' => $donation->phone,
            'allow_repeated_payments' => false,
            'redirect_url' => add_query_arg( array( 'listener' => 'instamojo_checkout' ), give_get_success_page_uri() ),
            'webhook' => add_query_arg( array( 'listener' => 'instamojo_checkout_webhook' ), home_url( '/' ) ),
        ) );
        $request = $client->parse_create( $response );
        if ( ! $request ) {
            give_insert_payment_note( $donation->id, 'Instamojo payment request was not confirmed; manual review may be needed.' );
            throw new PaymentGatewayException( __( 'Could not confirm an Instamojo payment request. Please contact the site administrator before trying again.', 'instamojo-for-give' ) );
        }
        give_update_meta( $donation->id, 'MG_instamojo_for_give_payment_request_id', $request['id'] );
        return new RedirectOffsite( $request['url'] );
    }
}
