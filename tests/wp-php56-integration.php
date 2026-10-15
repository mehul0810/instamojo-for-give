<?php
// Run with WP-CLI eval-file on a disposable WordPress 5.9 / GiveWP 2.19.2 site.
// This uses synthetic local records and never calls a payment provider.
if ( ! defined( 'ABSPATH' ) || ! defined( 'GIVE_VERSION' ) ) {
    throw new Exception( 'WordPress and GiveWP must be loaded.' );
}

function instamojo_check( $condition, $message ) {
    if ( ! $condition ) {
        throw new Exception( $message );
    }
}

instamojo_check( version_compare( PHP_VERSION, '5.6', '>=' ), 'PHP is below the declared floor.' );
instamojo_check( '2.19.2' === GIVE_VERSION, 'Unexpected GiveWP fixture version.' );
if ( ! function_exists( 'is_plugin_active' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
instamojo_check( is_plugin_active( 'instamojo-for-give/mg-instamojo-for-give.php' ), 'Gateway plugin is inactive.' );
instamojo_check( isset( give_get_payment_gateways()['instamojo_checkout'] ), 'Legacy gateway is not registered.' );
instamojo_check( ! class_exists( 'MG\\Instamojo\\GiveWP\\Includes\\ModernGateway', false ), 'Modern adapter loaded on old GiveWP.' );

give_update_option( 'test_mode', 'enabled' );
give_update_option( 'currency', 'INR' );
give_update_option( 'instamojo_get_test_api_key', 'synthetic-key' );
give_update_option( 'instamojo_get_test_auth_token', 'synthetic-token' );
instamojo_check( '1.1' === MG\Instamojo\GiveWP\Includes\ApiSelection::for_new_donation(), 'Existing v1.1 selection changed.' );

$form_id = wp_insert_post( array( 'post_title' => 'Synthetic compatibility form', 'post_type' => 'give_forms', 'post_status' => 'publish' ) );
instamojo_check( ! is_wp_error( $form_id ) && $form_id > 0, 'Synthetic form was not created.' );
$donation_id = give_insert_payment( array(
    'price' => '10.00',
    'give_form_title' => 'Synthetic compatibility form',
    'give_form_id' => $form_id,
    'date' => current_time( 'mysql' ),
    'user_email' => 'fixture@example.test',
    'purchase_key' => 'synthetic-only',
    'currency' => 'INR',
    'user_info' => array( 'id' => 0, 'first_name' => 'Fixture', 'last_name' => 'Donor', 'email' => 'fixture@example.test' ),
    'status' => 'pending',
    'gateway' => 'instamojo_checkout',
) );
instamojo_check( $donation_id > 0, 'Synthetic donation was not inserted.' );
give_update_meta( $donation_id, 'MG_instamojo_for_give_payment_request_id', 'synthetic-request' );
instamojo_check( 'test' === MG\Instamojo\GiveWP\Includes\ApiSelection::environment_for_donation( $donation_id ), 'Historical GiveWP mode was not retained.' );
instamojo_check( (int) $donation_id === (int) MG\Instamojo\GiveWP\Includes\Helpers::get_donation_id_by_meta( 'MG_instamojo_for_give_payment_request_id', 'synthetic-request' ), 'Historical donation lookup failed.' );
instamojo_check( 'instamojo_checkout' === give_get_payment_gateway( $donation_id ), 'Gateway ID changed.' );
instamojo_check( 'pending' === give_get_payment_status( $donation_id ), 'Synthetic donation status changed.' );
echo 'WordPress/GiveWP PHP 5.6 integration passed. Donation ' . $donation_id . " remained pending.\n";
