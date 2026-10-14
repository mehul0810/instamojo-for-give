<?php
// Run with: php tests/required-fields.php
define( 'ABSPATH', __DIR__ );

function give_get_chosen_gateway( $form_id ) {
    return $GLOBALS['chosen_gateway'];
}

function esc_html__( $text, $domain ) {
    return $text;
}

require __DIR__ . '/../src/Includes/Actions.php';

use MG\Instamojo\GiveWP\Includes\Actions;

$existing = [ 'give_email' => [ 'error_id' => 'invalid_email' ] ];
$GLOBALS['chosen_gateway'] = 'manual';
if ( Actions::require_phone_field( $existing, 42 ) !== $existing ) {
    fwrite( STDERR, "Other gateways must retain their required fields.\n" );
    exit( 1 );
}

$GLOBALS['chosen_gateway'] = 'instamojo_checkout';
$actual = Actions::require_phone_field( $existing, 42 );
if ( ! isset( $actual['give_email'], $actual['give_phone'] ) ||
    'invalid_phone' !== $actual['give_phone']['error_id'] ) {
    fwrite( STDERR, "Instamojo must add phone without dropping existing fields.\n" );
    exit( 1 );
}

echo "Required-field regression checks passed.\n";
