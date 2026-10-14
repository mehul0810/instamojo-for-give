<?php
// Run with: php tests/payment-lookup.php. Exercises the public GiveWP query contract.
define( 'ABSPATH', __DIR__ );

function give_get_payments( $args ) {
    $GLOBALS['lookup_args'] = $args;
    if ( 'missing' === $args['meta_query'][0]['value'] ) {
        return [];
    }
    return [ (object) [ 'ID' => 'duplicate' === $args['meta_query'][0]['value'] ? 43 : 42 ] ];
}

require __DIR__ . '/../src/Includes/Helpers.php';

use MG\Instamojo\GiveWP\Includes\Helpers;

$id = Helpers::get_donation_id_by_meta( 'MG_instamojo_for_give_payment_request_id', 'request-1' );
if ( 42 !== $id ||
    1 !== $GLOBALS['lookup_args']['number'] ||
    'DESC' !== $GLOBALS['lookup_args']['order'] ||
    'any' !== $GLOBALS['lookup_args']['status'] ||
    'MG_instamojo_for_give_payment_request_id' !== $GLOBALS['lookup_args']['meta_query'][0]['key'] ||
    'request-1' !== $GLOBALS['lookup_args']['meta_query'][0]['value'] ) {
    throw new Exception( 'Payment metadata lookup must return the newest exact match.' );
}

if ( 0 !== Helpers::get_donation_id_by_meta( 'MG_instamojo_for_give_payment_request_id', 'missing' ) ) {
    throw new Exception( 'Unknown payment requests must not select a donation.' );
}

if ( 43 !== Helpers::get_donation_id_by_meta( 'MG_instamojo_for_give_payment_request_id', 'duplicate' ) ) {
    throw new Exception( 'Duplicate request IDs must retain newest-donation lookup ordering.' );
}

$previous_args = $GLOBALS['lookup_args'];
if ( 0 !== Helpers::get_donation_id_by_meta( 'MG_instamojo_for_give_payment_request_id', '' ) ||
    $previous_args !== $GLOBALS['lookup_args'] ) {
    throw new Exception( 'Empty request IDs must not query or select a donation.' );
}

echo "Payment metadata lookup checks passed.\n";
