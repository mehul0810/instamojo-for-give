<?php
// Pure mock coverage. No network, database, credentials or provider payments.
define( 'ABSPATH', __DIR__ );
class FlowStopped extends Exception {}
class WP_Error { public $code; public function __construct( $code ) { $this->code = $code; } }
$GLOBALS['calls'] = array(); $GLOBALS['options'] = array(); $GLOBALS['meta'] = array(); $GLOBALS['status'] = 'pending'; $GLOBALS['transaction'] = '';
function record_call( $name, $value = null ) { $GLOBALS['calls'][] = array( $name, $value ); }
function add_action() {} function add_filter() {}
function give_get_errors() { return false; } function give_get_currency( $id ) { return isset( $GLOBALS['currency'] ) ? $GLOBALS['currency'] : 'INR'; }
function give_insert_payment( $data ) { record_call( 'insert', $data ); return 42; }
function give_maybe_sanitize_amount( $amount ) { return $amount; }
function give_get_success_page_uri() { return 'https://example.test/success'; }
function home_url( $path ) { return 'https://example.test' . $path; }
function add_query_arg( $args, $url ) { return $url . '?listener=' . $args['listener']; }
function wp_remote_post( $url, $args ) { record_call( 'post', array( $url, $args ) ); return isset( $GLOBALS['responses'][$url] ) ? $GLOBALS['responses'][$url] : new WP_Error( 'unmocked' ); }
function wp_remote_get( $url, $args ) { record_call( 'get', array( $url, $args ) ); return isset( $GLOBALS['responses'][$url] ) ? $GLOBALS['responses'][$url] : new WP_Error( 'unmocked' ); }
function wp_remote_retrieve_body( $response ) { return $response['body']; }
function wp_remote_retrieve_response_code( $response ) { return $response['code']; }
function is_wp_error( $thing ) { return $thing instanceof WP_Error; }
function get_transient( $key ) { return false; } function set_transient( $key, $value, $expiry ) { record_call( 'transient', $expiry ); }
function give_update_meta( $id, $key, $value ) { $GLOBALS['meta'][$key] = $value; record_call( 'meta', array( $id, $key, $value ) ); }
function give_get_meta( $id, $key, $single ) { return isset( $GLOBALS['meta'][$key] ) ? $GLOBALS['meta'][$key] : ''; }
function wp_redirect( $url ) { record_call( 'redirect', $url ); }
function give_die() { throw new FlowStopped(); } function wp_die( $message, $title = '', $args = array() ) { record_call( 'http_result', isset( $args['response'] ) ? $args['response'] : 200 ); throw new FlowStopped(); }
function give_set_error( $id, $message ) { record_call( 'error', array( $id, $message ) ); }
function give_update_payment_status( $id, $status ) { $GLOBALS['status'] = $status; record_call( 'status', array( $id, $status ) ); }
function give_insert_payment_note( $id, $note ) { record_call( 'note', array( $id, $note ) ); }
function give_send_back_to_checkout( $query ) { record_call( 'checkout', $query ); throw new FlowStopped(); }
function give_clean( $input ) { return $input; } function wp_unslash( $input ) { return $input; }
function give_set_payment_transaction_id( $id, $transaction ) { $GLOBALS['transaction'] = $transaction; $GLOBALS['meta']['_give_payment_transaction_id'] = $transaction; record_call( 'transaction', array( $id, $transaction ) ); }
function give_get_payment_transaction_id( $id ) { return $GLOBALS['transaction']; }
function give_get_payment_status( $id ) { return $GLOBALS['status']; }
function give_get_payment_gateway( $id ) { return 'instamojo_checkout'; }
function give_get_payment_amount( $id ) { return 100; }
function give_get_payment_currency_code( $id ) { return 'INR'; }
function give_send_to_success_page() { record_call( 'success' ); throw new FlowStopped(); }
function give_get_failed_transaction_uri() { return 'https://example.test/failed'; }
function esc_html__( $text, $domain = '' ) { return $text; }
function apply_filters( $name, $value ) { return $value; }
function give_is_test_mode() { return true; }
function give_get_option( $name ) { return isset( $GLOBALS['options'][$name] ) ? $GLOBALS['options'][$name] : ''; }
function give_get_payments( $args ) { if ( isset( $args['meta_query'][0]['key'] ) && '_give_payment_transaction_id' === $args['meta_query'][0]['key'] ) { return isset( $GLOBALS['other_transaction_owner'] ) ? array( (object) array( 'ID' => 42 ), (object) array( 'ID' => $GLOBALS['other_transaction_owner'] ) ) : array(); } return array( (object) array( 'ID' => 42 ) ); }
function assert_true( $yes, $message ) { if ( ! $yes ) { throw new Exception( $message ); } }
function called( $name, $value = null ) { foreach ( $GLOBALS['calls'] as $call ) { if ( $call[0] === $name && ( null === $value || $call[1] === $value ) ) { return true; } } return false; }
function response( $body, $code ) { return array( 'body' => $body, 'code' => $code ); }
function run_flow( $callback ) { try { $callback(); } catch ( FlowStopped $stop ) {} }
require __DIR__ . '/../src/Includes/Helpers.php';
require __DIR__ . '/../src/Includes/ApiSelection.php';
require __DIR__ . '/../src/Includes/ApiClient.php';
require __DIR__ . '/../src/Includes/Actions.php';
$actions = new MG\Instamojo\GiveWP\Includes\Actions();
$data = array( 'post_data' => array( 'give-form-id' => 7, 'give-form-title' => 'Relief', 'payment-mode' => 'instamojo_checkout', 'give_phone' => '1234567890' ), 'user_email' => 'donor@example.test', 'purchase_key' => 'fixture', 'price' => '100', 'user_info' => array( 'first_name' => 'Donor' ), 'gateway' => 'instamojo_checkout', 'date' => '2026-01-01' );
$GLOBALS['options'] = array( 'instamojo_get_test_api_key' => 'legacy-key', 'instamojo_get_test_auth_token' => 'legacy-token', 'instamojo_get_test_salt' => 'legacy-salt' );
assert_true( '1.1' === MG\Instamojo\GiveWP\Includes\ApiSelection::for_new_donation(), 'Legacy selection changed.' );
$GLOBALS['responses'] = array( 'https://test.instamojo.com/api/1.1/payment-requests/' => response( '{"success":true,"payment_request":{"id":"request-1","longurl":"https://example.test/pay"}}', 201 ) );
run_flow( function () use ( $actions, $data ) { $actions->process_donation( $data ); } );
assert_true( called( 'redirect', 'https://example.test/pay' ), 'Legacy request did not redirect.' );
assert_true( '1.1' === $GLOBALS['meta']['MG_instamojo_for_give_api_version'], 'Legacy version was not saved.' );
assert_true( 'test' === $GLOBALS['meta']['MG_instamojo_for_give_environment'], 'Environment was not saved.' );
foreach ( $GLOBALS['calls'] as $call ) { if ( 'post' === $call[0] ) { assert_true( false === $call[1][1]['body']['allow_repeated_payments'], 'New request permits repeated payment.' ); } }
$GLOBALS['currency'] = 'USD'; $GLOBALS['calls'] = array();
run_flow( function () use ( $actions, $data ) { $actions->process_donation( $data ); } );
assert_true( ! called( 'insert' ) && ! called( 'post' ) && called( 'checkout' ), 'Non-INR donation reached payment creation.' );
unset( $GLOBALS['currency'] );

$GLOBALS['options']['mg_instamojo_api_version'] = 'v2';
$GLOBALS['options']['mg_instamojo_v2_test_client_id'] = 'client-id';
$GLOBALS['calls'] = array();
run_flow( function () use ( $actions, $data ) { $actions->process_donation( $data ); } );
assert_true( ! called( 'insert' ) && called( 'checkout' ), 'Incomplete v2 setup created a pending donation.' );
$GLOBALS['options']['mg_instamojo_v2_test_client_secret'] = 'client-secret';
$GLOBALS['calls'] = array();
run_flow( function () use ( $actions, $data ) { $actions->process_donation( $data ); } );
assert_true( ! called( 'insert' ) && called( 'checkout' ), 'Missing v2 webhook salt created a payable donation.' );
$GLOBALS['options']['mg_instamojo_v2_test_webhook_salt'] = 'v2-salt';
$GLOBALS['responses'] = array( 'https://test.instamojo.com/oauth2/token/' => response( '{"access_token":"mock-token","expires_in":3600}', 200 ), 'https://test.instamojo.com/v2/payment_requests/' => response( '{"id":"request-v2","longurl":"https://example.test/pay-v2"}', 200 ) );
$GLOBALS['calls'] = array();
run_flow( function () use ( $actions, $data ) { $actions->process_donation( $data ); } );
assert_true( called( 'redirect', 'https://example.test/pay-v2' ), 'v2 request did not redirect.' );
assert_true( 'v2' === $GLOBALS['meta']['MG_instamojo_for_give_api_version'], 'v2 selection was not saved.' );
assert_true( called( 'post' ), 'No v2 request was made.' );
$GLOBALS['responses']['https://test.instamojo.com/v2/payment_requests/'] = response( 'not-json', 502 );
$GLOBALS['calls'] = array(); $GLOBALS['status'] = 'pending';
run_flow( function () use ( $actions, $data ) { $actions->process_donation( $data ); } );
assert_true( 'pending' === $GLOBALS['status'] && called( 'checkout' ), 'Ambiguous create response changed status.' );
// A pending historical v1.1 donation continues to verify with v1.1 after selecting v2.
$GLOBALS['meta'] = array( 'MG_instamojo_for_give_payment_request_id' => 'request-1', '_give_payment_mode' => 'test' );
$GLOBALS['status'] = 'pending'; $GLOBALS['transaction'] = ''; unset( $GLOBALS['meta']['_give_payment_transaction_id'] ); $GLOBALS['calls'] = array();
$GLOBALS['responses']['https://test.instamojo.com/api/1.1/payments/payment-1'] = response( '{"payment":{"payment_id":"payment-1","payment_request":"https://www.instamojo.com/api/1.1/payment-requests/request-1/","amount":"100","currency":"INR","status":"Credit"}}', 200 );
$_GET = array( 'listener' => 'instamojo_checkout', 'payment_request_id' => 'request-1', 'payment_id' => 'payment-1' );
run_flow( function () use ( $actions ) { $actions->listen_to_response(); } );
assert_true( 'publish' === $GLOBALS['status'] && 'payment-1' === $GLOBALS['transaction'], 'Historical v1.1 payment did not complete.' );
$GLOBALS['status'] = 'pending'; $GLOBALS['transaction'] = ''; unset( $GLOBALS['meta']['_give_payment_mode'], $GLOBALS['meta']['_give_payment_transaction_id'] ); $GLOBALS['calls'] = array();
run_flow( function () use ( $actions ) { $actions->listen_to_response(); } );
assert_true( 'pending' === $GLOBALS['status'] && ! called( 'get' ) && ! called( 'status' ) && called( 'http_result', 200 ), 'Unknown historical environment reached provider on return.' );
$_GET = array( 'listener' => 'instamojo_checkout_webhook' ); $_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = array( 'amount' => '100', 'payment_id' => 'payment-1', 'payment_request_id' => 'request-1', 'status' => 'Credit', 'mac' => hash_hmac( 'sha1', '100|payment-1|request-1|Credit', 'legacy-salt' ) );
$GLOBALS['calls'] = array(); run_flow( function () use ( $actions ) { $actions->listen_to_response(); } );
assert_true( 'pending' === $GLOBALS['status'] && ! called( 'get' ) && called( 'http_result', 503 ), 'Unknown historical environment reached provider on webhook.' );
$GLOBALS['meta']['_give_payment_mode'] = 'test'; $GLOBALS['status'] = 'publish'; $GLOBALS['transaction'] = 'payment-1'; $_GET = array( 'listener' => 'instamojo_checkout', 'payment_request_id' => 'request-1', 'payment_id' => 'payment-1' );
$GLOBALS['calls'] = array(); run_flow( function () use ( $actions ) { $actions->listen_to_response(); } );
assert_true( ! called( 'status' ), 'Duplicate callback changed status.' );
$GLOBALS['status'] = 'pending'; $GLOBALS['transaction'] = ''; unset( $GLOBALS['meta']['_give_payment_transaction_id'] ); $GLOBALS['calls'] = array();
$GLOBALS['responses']['https://test.instamojo.com/api/1.1/payments/payment-1'] = response( '{"payment":{"payment_id":"payment-1","payment_request":"wrong","amount":"100","currency":"INR","status":"Credit"}}', 200 );
run_flow( function () use ( $actions ) { $actions->listen_to_response(); } );
assert_true( 'pending' === $GLOBALS['status'], 'Mismatched request completed a donation.' );
$GLOBALS['responses']['https://test.instamojo.com/api/1.1/payments/payment-1'] = response( 'bad response', 502 );
run_flow( function () use ( $actions ) { $actions->listen_to_response(); } );
assert_true( 'pending' === $GLOBALS['status'], 'Provider error failed a pending donation.' );
$webhook = array( 'amount' => '100', 'payment_id' => 'payment-1', 'payment_request_id' => 'request-1', 'status' => 'Credit' );
$mac = hash_hmac( 'sha1', '100|payment-1|request-1|Credit', 'legacy-salt' );
assert_true( MG\Instamojo\GiveWP\Includes\Actions::verify_webhook_mac( $webhook, $mac, 'legacy-salt' ), 'Valid MAC rejected.' );
assert_true( ! MG\Instamojo\GiveWP\Includes\Actions::verify_webhook_mac( $webhook, 'bad', 'legacy-salt' ), 'Invalid MAC accepted.' );
$GLOBALS['responses']['https://test.instamojo.com/api/1.1/payments/payment-1'] = response( '{"payment":{"payment_id":"payment-1","payment_request":"https://www.instamojo.com/api/1.1/payment-requests/request-1/","amount":"100","currency":"INR","status":"Credit"}}', 200 );
$GLOBALS['status'] = 'pending'; $GLOBALS['transaction'] = ''; unset( $GLOBALS['meta']['_give_payment_transaction_id'] ); $GLOBALS['calls'] = array();
$_GET = array( 'listener' => 'instamojo_checkout_webhook' ); $_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = $webhook; $_POST['mac'] = 'invalid';
run_flow( function () use ( $actions ) { $actions->listen_to_response(); } );
assert_true( 'pending' === $GLOBALS['status'] && ! called( 'get' ) && called( 'http_result', 403 ), 'Invalid webhook reached provider lookup.' );
$_POST['mac'] = $mac;
run_flow( function () use ( $actions ) { $actions->listen_to_response(); } );
assert_true( 'publish' === $GLOBALS['status'], 'Valid webhook did not reconcile.' );
$GLOBALS['calls'] = array();
run_flow( function () use ( $actions ) { $actions->listen_to_response(); } );
assert_true( 'publish' === $GLOBALS['status'] && ! called( 'status' ) && called( 'http_result', 200 ), 'Webhook replay changed a completed donation.' );

$GLOBALS['meta']['MG_instamojo_for_give_api_version'] = 'v2';
$GLOBALS['meta']['MG_instamojo_for_give_payment_request_id'] = 'request-v2';
$GLOBALS['status'] = 'pending'; $GLOBALS['transaction'] = ''; unset( $GLOBALS['meta']['_give_payment_transaction_id'] ); $GLOBALS['calls'] = array();
$GLOBALS['responses']['https://test.instamojo.com/v2/payments/payment-v2/'] = response( '{"id":"payment-v2","payment_request":"https://api.instamojo.com/v2/payment_requests/request-v2/","amount":"100","currency":"INR","status":true}', 200 );
$_GET = array( 'listener' => 'instamojo_checkout', 'payment_request_id' => 'request-v2', 'payment_id' => 'payment-v2' );
run_flow( function () use ( $actions ) { $actions->listen_to_response(); } );
assert_true( 'publish' === $GLOBALS['status'], 'v2 return did not reconcile.' );
$GLOBALS['status'] = 'pending'; $GLOBALS['transaction'] = ''; unset( $GLOBALS['meta']['_give_payment_transaction_id'] ); $GLOBALS['calls'] = array();
$GLOBALS['meta']['MG_instamojo_for_give_account_fingerprint'] = 'different-account';
run_flow( function () use ( $actions ) { $actions->listen_to_response(); } );
assert_true( 'pending' === $GLOBALS['status'] && ! called( 'get' ), 'Changed account was used to reconcile.' );
// A failed attempt can be followed by a different successful attempt on a historical request.
$GLOBALS['meta'] = array( 'MG_instamojo_for_give_payment_request_id' => 'request-1', '_give_payment_mode' => 'test' );
$GLOBALS['status'] = 'pending'; $GLOBALS['transaction'] = ''; unset( $GLOBALS['meta']['_give_payment_transaction_id'] ); $GLOBALS['calls'] = array();
$GLOBALS['responses']['https://test.instamojo.com/api/1.1/payments/payment-1'] = response( '{"payment":{"payment_id":"payment-1","payment_request":"https://www.instamojo.com/api/1.1/payment-requests/request-1/","amount":"100","currency":"INR","status":"Failed"}}', 200 );
$_GET = array( 'listener' => 'instamojo_checkout', 'payment_request_id' => 'request-1', 'payment_id' => 'payment-1' );
run_flow( function () use ( $actions ) { $actions->listen_to_response(); } );
assert_true( 'failed' === $GLOBALS['status'], 'Definite failure did not mark pending donation failed.' );
$GLOBALS['responses']['https://test.instamojo.com/api/1.1/payments/payment-2'] = response( '{"payment":{"payment_id":"payment-2","payment_request":"https://www.instamojo.com/api/1.1/payment-requests/request-1/","amount":"100","currency":"INR","status":"Credit"}}', 200 );
$_GET['payment_id'] = 'payment-2'; run_flow( function () use ( $actions ) { $actions->listen_to_response(); } );
assert_true( 'publish' === $GLOBALS['status'] && 'payment-2' === $GLOBALS['transaction'], 'Later verified success did not recover a failed donation.' );
$GLOBALS['calls'] = array(); run_flow( function () use ( $actions ) { $actions->listen_to_response(); } );
assert_true( ! called( 'status' ), 'Replay re-mutated completed donation.' );
// Provider amount/currency and transaction ownership must independently match.
foreach ( array( '{"payment":{"payment_id":"payment-1","payment_request":"request-1","amount":"99","currency":"INR","status":"Credit"}}', '{"payment":{"payment_id":"payment-1","payment_request":"request-1","amount":"100","currency":"USD","status":"Credit"}}' ) as $mismatch ) {
    $GLOBALS['status'] = 'pending'; $GLOBALS['transaction'] = ''; unset( $GLOBALS['meta']['_give_payment_transaction_id'] );
    $GLOBALS['responses']['https://test.instamojo.com/api/1.1/payments/payment-1'] = response( $mismatch, 200 );
    $_GET['payment_id'] = 'payment-1'; run_flow( function () use ( $actions ) { $actions->listen_to_response(); } );
    assert_true( 'pending' === $GLOBALS['status'], 'Amount or currency mismatch completed donation.' );
}
$GLOBALS['responses']['https://test.instamojo.com/api/1.1/payments/payment-1'] = response( '{"payment":{"payment_id":"payment-1","payment_request":"request-1","amount":"100","currency":"INR","status":"Credit"}}', 200 );
$GLOBALS['other_transaction_owner'] = 99;
run_flow( function () use ( $actions ) { $actions->listen_to_response(); } );
assert_true( 'pending' === $GLOBALS['status'], 'Another donation owned this transaction.' );
unset( $GLOBALS['other_transaction_owner'] );
// A validly signed webhook with a transient provider failure requests a retry.
$_GET = array( 'listener' => 'instamojo_checkout_webhook' ); $_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = $webhook; $_POST['mac'] = $mac; $GLOBALS['calls'] = array();
$GLOBALS['responses']['https://test.instamojo.com/api/1.1/payments/payment-1'] = response( 'bad gateway', 503 );
run_flow( function () use ( $actions ) { $actions->listen_to_response(); } );
assert_true( 'pending' === $GLOBALS['status'] && called( 'http_result', 503 ), 'Transient webhook failure did not request retry.' );
$_POST['amount'] = '999'; $GLOBALS['calls'] = array();
run_flow( function () use ( $actions ) { $actions->listen_to_response(); } );
assert_true( called( 'http_result', 403 ) && ! called( 'get' ), 'Tampered webhook reached provider lookup.' );
echo "Versioned payment flow checks passed.\n";
