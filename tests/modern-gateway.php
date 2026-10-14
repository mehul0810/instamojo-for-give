<?php
namespace Give\Donations\Models { class Donation { public $id = 42; public $formTitle = 'Fixture'; public $firstName = 'Test'; public $lastName = 'Donor'; public $email = 'test@example.test'; public $phone = '9999999999'; public $amount; public $mode; } }
namespace Give\Framework\PaymentGateways { abstract class PaymentGateway {} }
namespace Give\Framework\PaymentGateways\Commands { class RedirectOffsite { public $redirectUrl; public function __construct( $url ) { $this->redirectUrl = $url; } } }
namespace Give\Framework\PaymentGateways\Exceptions { class PaymentGatewayException extends \Exception {} }
namespace {
define( 'ABSPATH', __DIR__ ); define( 'MG_INSTAMOJO_FOR_GIVE_VERSION', '1.1.0' );
class WP_Error { public function __construct( $a, $b = '' ) {} }
$GLOBALS['options'] = array( 'mg_instamojo_api_version' => 'v2', 'mg_instamojo_v2_test_client_id' => 'synthetic-id', 'mg_instamojo_v2_test_client_secret' => 'synthetic-secret', 'mg_instamojo_v2_test_webhook_salt' => 'synthetic-salt' );
$GLOBALS['meta'] = array(); $GLOBALS['requests'] = array();
function give_get_option( $k ) { return isset( $GLOBALS['options'][$k] ) ? $GLOBALS['options'][$k] : ''; }
function give_update_meta( $id, $k, $v ) { $GLOBALS['meta'][$k] = $v; }
function give_insert_payment_note( $id, $note ) {}
function apply_filters( $k, $v ) { return $v; }
function give_is_test_mode() { return true; }
function __( $s, $d ) { return $s; }
function home_url( $s ) { return 'https://example.test' . $s; }
function give_get_success_page_uri() { return 'https://example.test/success'; }
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
function get_transient( $k ) { return false; }
function set_transient( $k, $v, $t ) {}
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function wp_remote_retrieve_response_code( $v ) { return $v['code']; }
function wp_remote_retrieve_body( $v ) { return $v['body']; }
function wp_remote_post( $url, $args ) { $GLOBALS['requests'][] = array( $url, $args ); if ( false !== strpos( $url, '/oauth2/token/' ) ) return array( 'code' => 200, 'body' => '{"access_token":"synthetic","expires_in":3600}' ); return array( 'code' => 200, 'body' => '{"id":"request-v2","longurl":"https://test.instamojo.com/pay/request-v2"}' ); }
function wp_register_script( $a, $b, $c, $d, $e ) { $GLOBALS['script'] = $a; }
function wp_enqueue_script( $a ) {}
function wp_add_inline_script( $a, $s ) { $GLOBALS['inline'] = $s; }
function check( $ok, $message ) { if ( ! $ok ) throw new \Exception( $message ); }
require __DIR__ . '/../src/Includes/ApiSelection.php'; require __DIR__ . '/../src/Includes/ApiClient.php'; require __DIR__ . '/../src/Includes/ModernGateway.php';
$donation = new \Give\Donations\Models\Donation();
$donation->amount = new class { public function getCurrency() { return new class { public function getCode() { return 'INR'; } }; } public function formatToDecimal() { return '50.00'; } };
$donation->mode = new class { public function isTest() { return true; } };
$gateway = new \MG\Instamojo\GiveWP\Includes\ModernGateway();
check( 'instamojo_checkout' === $gateway::id() && array( 3 ) === $gateway->supportsFormVersions(), 'Modern gateway identity or form support changed.' );
check( false === $gateway->supportsRefund(), 'Refund was advertised.' );
$gateway->enqueueScript( 1 ); check( false !== strpos( $GLOBALS['inline'], 'window.givewp.gateways.register' ), 'Visual Builder fields were not registered.' );
$command = $gateway->createPayment( $donation, array() );
check( $command instanceof \Give\Framework\PaymentGateways\Commands\RedirectOffsite && 'https://test.instamojo.com/pay/request-v2' === $command->redirectUrl, 'Offsite redirect failed.' );
check( 'v2' === $GLOBALS['meta']['MG_instamojo_for_give_api_version'] && 'test' === $GLOBALS['meta']['MG_instamojo_for_give_environment'] && 'request-v2' === $GLOBALS['meta']['MG_instamojo_for_give_payment_request_id'], 'Donation metadata changed.' );
check( false === $GLOBALS['requests'][1][1]['body']['allow_repeated_payments'], 'Repeated payments were enabled.' );
echo "Modern gateway contract checks passed.\n";
}
