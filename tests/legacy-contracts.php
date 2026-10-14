<?php
// Isolated contract checks. These do not boot WordPress or GiveWP.
define( 'ABSPATH', __DIR__ );
function add_action() {}
function add_filter() {}
function esc_html__( $text, $domain ) { return $text; }
function esc_url_raw( $url ) { return $url; }
function give_get_current_setting_section() { return $GLOBALS['setting_section']; }
function give_is_test_mode() { return $GLOBALS['test_mode']; }
function give_get_option( $key ) { return isset( $GLOBALS['options'][$key] ) ? $GLOBALS['options'][$key] : ''; }
function apply_filters( $key, $value ) { return 'instamojo_for_give_api_version' === $key && isset( $GLOBALS['version_filter'] ) ? $GLOBALS['version_filter'] : $value; }
function check( $ok, $message ) { if ( ! $ok ) { throw new Exception( $message ); } }

require __DIR__ . '/../src/Includes/Filters.php';
require __DIR__ . '/../src/Admin/Settings.php';
require __DIR__ . '/../src/Includes/Helpers.php';
require __DIR__ . '/../src/Includes/ApiSelection.php';

$GLOBALS['options'] = [];
check( 'v2' === MG\Instamojo\GiveWP\Includes\ApiSelection::for_new_donation(), 'Fresh install must select v2.' );
$GLOBALS['options']['instamojo_get_test_api_key'] = 'test-key';
$GLOBALS['options']['instamojo_get_test_auth_token'] = 'test-token';
check( '1.1' === MG\Instamojo\GiveWP\Includes\ApiSelection::for_new_donation(), 'Configured upgrade must stay v1.1.' );
$GLOBALS['options']['mg_instamojo_api_version'] = 'v2';
check( 'v2' === MG\Instamojo\GiveWP\Includes\ApiSelection::for_new_donation(), 'Explicit v2 switch failed.' );
$GLOBALS['version_filter'] = '1.1';
check( 'v2' === MG\Instamojo\GiveWP\Includes\ApiSelection::for_new_donation(), 'Legacy filter overrode explicit v2 choice.' );
unset( $GLOBALS['version_filter'] );

$GLOBALS['options']['mg_instamojo_api_version'] = '1.1';
check( '1.1' === MG\Instamojo\GiveWP\Includes\ApiSelection::for_new_donation(), 'Explicit v1.1 switch failed.' );
$gateway = new MG\Instamojo\GiveWP\Includes\Filters();
$registered = $gateway->register_gateways( [ 'manual' => [ 'admin_label' => 'Manual' ] ] );
check( isset( $registered['manual'], $registered['instamojo_checkout'] ), 'Gateway ID or existing gateways changed.' );

$GLOBALS['setting_section'] = 'instamojo';
$settings = new MG\Instamojo\GiveWP\Admin\Settings();
$ids = array_map( function ( $item ) { return $item['id']; }, $settings->register_settings( [] ) );
$expected = [
    'instamojo_get_live_api_key', 'instamojo_get_live_auth_token', 'instamojo_get_live_salt',
    'instamojo_get_test_api_key', 'instamojo_get_test_auth_token', 'instamojo_get_test_salt',
    'instamojo_enable_billing_address',
];
foreach ( $expected as $id ) { check( in_array( $id, $ids, true ), "Missing legacy setting {$id}." ); }
$all_settings = $settings->register_settings( [] );
foreach ( $all_settings as $field ) {
    if ( 'mg_instamojo_api_version' === $field['id'] ) { check( '1.1' === $field['default'], 'Configured settings field default differs from selection.' ); }
}
$_GET = [ 'page' => 'give-settings', 'tab' => 'gateways', 'section' => 'instamojo' ];
ob_start(); $settings->legacy_api_notice(); $notice = ob_get_clean();
check( false !== strpos( $notice, '1.2.0' ), 'Legacy settings page notice absent.' );
$_GET['section'] = 'manual';
ob_start(); $settings->legacy_api_notice(); $notice = ob_get_clean();
check( '' === $notice, 'Legacy notice escaped its settings page.' );
$GLOBALS['options'] = [];
$all_settings = $settings->register_settings( [] );
foreach ( $all_settings as $field ) {
    if ( 'mg_instamojo_api_version' === $field['id'] ) { check( 'v2' === $field['default'], 'Fresh settings field default differs from selection.' ); }
}


$GLOBALS['options'] = [
    'instamojo_get_live_auth_token' => ' live-fixture ',
    'instamojo_get_test_auth_token' => ' test-fixture ',
];
$GLOBALS['test_mode'] = false;
check( 'Bearer live-fixture' === MG\Instamojo\GiveWP\Includes\Helpers::get_headers()['Authorization'], 'Live saved token changed.' );
$GLOBALS['test_mode'] = true;
check( 'Bearer test-fixture' === MG\Instamojo\GiveWP\Includes\Helpers::get_headers()['Authorization'], 'Test saved token changed.' );
$readme = file_get_contents( __DIR__ . '/../readme.txt' );
$upgrade_notice = explode( '== Upgrade Notice ==', $readme, 2 );
check( isset( $upgrade_notice[1] ) && false !== strpos( $upgrade_notice[1], '1.2.0' ), 'Readme upgrade notice missing API deprecation plan.' );
echo "Legacy contract checks passed.\n";
