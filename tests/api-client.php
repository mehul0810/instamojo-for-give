<?php
// Deterministic transport/cache checks: all HTTP and storage are in memory.
define( 'ABSPATH', __DIR__ );
class WP_Error { public function __construct( $code, $message = '' ) {} }
$GLOBALS['options'] = array(); $GLOBALS['transients'] = array(); $GLOBALS['calls'] = array(); $GLOBALS['reply_queue'] = array();
function give_get_option( $key ) { return isset( $GLOBALS['options'][$key] ) ? $GLOBALS['options'][$key] : ''; }
function get_transient( $key ) { return isset( $GLOBALS['transients'][$key] ) ? $GLOBALS['transients'][$key] : false; }
function set_transient( $key, $value, $ttl ) { $GLOBALS['transients'][$key] = $value; $GLOBALS['calls'][] = array( 'cache', $key, $ttl ); }
function delete_transient( $key ) { unset( $GLOBALS['transients'][$key] ); $GLOBALS['calls'][] = array( 'delete', $key ); }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function wp_remote_retrieve_response_code( $response ) { return $response['code']; }
function wp_remote_retrieve_body( $response ) { return $response['body']; }
function reply( $code, $body ) { return array( 'code' => $code, 'body' => $body ); }
function dispatch( $method, $url, $args ) { $GLOBALS['calls'][] = array( $method, $url, $args ); if ( ! isset( $GLOBALS['reply_queue'][$url] ) || ! count( $GLOBALS['reply_queue'][$url] ) ) { throw new Exception( 'Unmocked request: ' . $url ); } return array_shift( $GLOBALS['reply_queue'][$url] ); }
function wp_remote_post( $url, $args ) { return dispatch( 'POST', $url, $args ); }
function wp_remote_get( $url, $args ) { return dispatch( 'GET', $url, $args ); }
function check( $ok, $message ) { if ( ! $ok ) { throw new Exception( $message ); } }
function queue( $url, $responses ) { $GLOBALS['reply_queue'][$url] = $responses; }
function requests( $url ) { $found = array(); foreach ( $GLOBALS['calls'] as $call ) { if ( isset( $call[1] ) && $call[1] === $url ) { $found[] = $call; } } return $found; }
function apply_filters( $name, $value ) { return $value; }
function give_is_test_mode() { return true; }
require __DIR__ . '/../src/Includes/ApiSelection.php';
require __DIR__ . '/../src/Includes/ApiClient.php';
require __DIR__ . '/../src/Includes/Instamojo.php';
$client_class = 'MG\\Instamojo\\GiveWP\\Includes\\ApiClient';
$GLOBALS['options'] = array(
 'mg_instamojo_v2_test_client_id' => 'test-id', 'mg_instamojo_v2_test_client_secret' => 'test-secret',
 'mg_instamojo_v2_live_client_id' => 'live-id', 'mg_instamojo_v2_live_client_secret' => 'live-secret',
 'instamojo_get_test_api_key' => 'legacy-test-key', 'instamojo_get_test_auth_token' => 'legacy-test-token',
 'instamojo_get_live_api_key' => 'legacy-live-key', 'instamojo_get_live_auth_token' => 'legacy-live-token',
);
$test_oauth = 'https://test.instamojo.com/oauth2/token/'; $live_oauth = 'https://api.instamojo.com/oauth2/token/';
$test_payment = 'https://test.instamojo.com/v2/payments/MOJO1/'; $live_payment = 'https://api.instamojo.com/v2/payments/MOJO1/';
queue( $test_oauth, array( reply( 200, '{"access_token":"test-token","expires_in":3600}' ), reply( 200, '{"access_token":"new-test-token","expires_in":3600}' ), reply( 200, '{"access_token":"changed-token","expires_in":3600}' ) ) );
queue( $live_oauth, array( reply( 200, '{"access_token":"live-token","expires_in":3600}' ) ) );
queue( $test_payment, array( reply( 200, '{}' ), reply( 200, '{}' ), reply( 401, '{}' ), reply( 200, '{}' ), reply( 503, '{}' ), reply( 200, '{}' ) ) );
queue( $live_payment, array( reply( 200, '{}' ) ) );
$test = new $client_class( 'v2', 'test' ); $live = new $client_class( 'v2', 'live' );
$test->payment( 'MOJO1' ); $test->payment( 'MOJO1' ); $live->payment( 'MOJO1' );
check( 1 === count( requests( $test_oauth ) ) && 1 === count( requests( $live_oauth ) ), 'Token cache or environment separation failed.' );
check( requests( $test_payment )[0][2]['headers']['Authorization'] === 'Bearer test-token', 'Test auth header wrong.' );
check( requests( $live_payment )[0][2]['headers']['Authorization'] === 'Bearer live-token', 'Live auth header wrong.' );
$test->payment( 'MOJO1' );
check( 2 === count( requests( $test_oauth ) ) && 4 === count( requests( $test_payment ) ), '401 did not retry exactly once.' );
check( requests( $test_payment )[3][2]['headers']['Authorization'] === 'Bearer new-test-token', '401 retry did not refresh token.' );
$test->payment( 'MOJO1' );
check( 5 === count( requests( $test_payment ) ) && 2 === count( requests( $test_oauth ) ), 'Transient 5xx was retried or token invalidated.' );
$GLOBALS['options']['mg_instamojo_v2_test_client_secret'] = 'rotated-secret';
$test->payment( 'MOJO1' );
check( 3 === count( requests( $test_oauth ) ) && requests( $test_payment )[5][2]['headers']['Authorization'] === 'Bearer changed-token', 'Credential edit reused stale token.' );
$legacy_test = new $client_class( '1.1', 'test' ); $legacy_live = new $client_class( '1.1', 'live' );
queue( 'https://test.instamojo.com/api/1.1/payment-requests/', array( reply( 201, '{"payment_request":{"id":"x","longurl":"https://example.test/x"}}' ) ) );
queue( 'https://www.instamojo.com/api/1.1/payment-requests/', array( reply( 201, '{"payment_request":{"id":"y","longurl":"https://example.test/y"}}' ) ) );
$legacy_test->create( array( 'amount' => '100' ) ); $legacy_live->create( array( 'amount' => '100' ) );
check( requests( 'https://test.instamojo.com/api/1.1/payment-requests/' )[0][2]['headers']['X-Api-Key'] === 'legacy-test-key', 'Legacy test credentials wrong.' );
check( requests( 'https://www.instamojo.com/api/1.1/payment-requests/' )[0][2]['headers']['X-Api-Key'] === 'legacy-live-key', 'Legacy live credentials wrong.' );
check( 1 === count( requests( $live_oauth ) ), 'Legacy transport used v2 OAuth.' );
queue( 'https://test.instamojo.com/v2/payment_requests/', array( reply( 401, '{}' ), reply( 200, '{"id":"v2-request","longurl":"https://example.test/v2"}' ) ) );
queue( $test_oauth, array( reply( 200, '{"access_token":"retry-create-token","expires_in":3600}' ) ) );
$created = $test->create( array( 'amount' => '100' ) );
check( 200 === $created['code'] && 2 === count( requests( 'https://test.instamojo.com/v2/payment_requests/' ) ), 'Definite 401 create rejection was not retried once.' );
check( requests( 'https://test.instamojo.com/v2/payment_requests/' )[1][2]['headers']['Authorization'] === 'Bearer retry-create-token', 'Create retry reused rejected token.' );
queue( 'https://test.instamojo.com/api/1.1/payment-requests/', array( reply( 201, '{"payment_request":{"id":"z","longurl":"https://example.test/z"}}' ) ) );
MG\Instamojo\GiveWP\Includes\Instamojo::create_payment_request( array( 'amount' => 100 ), '1.1', 'test' );
check( 2 === count( requests( 'https://test.instamojo.com/api/1.1/payment-requests/' ) ), 'Public facade stopped delegating to versioned client.' );
echo "API client environment and cache checks passed.\n";
