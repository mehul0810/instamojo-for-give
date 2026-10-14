<?php
// This file intentionally uses PHP 5.6 syntax. The modern adapter must stay unloaded
// when GiveWP does not provide its modern gateway base class.
namespace MG\Instamojo\GiveWP;
define( 'ABSPATH', __DIR__ );
define( 'MG_INSTAMOJO_FOR_GIVE_PLUGIN_FILE', __FILE__ );
$GLOBALS['hooks'] = array();
function wp_die( $message ) { throw new \Exception( $message ); }
function add_action( $name, $callback ) { $GLOBALS['hooks'][ $name ] = $callback; }
function register_activation_hook( $file, $callback ) {}
function register_deactivation_hook( $file, $callback ) {}
spl_autoload_register( function ( $name ) {
    if ( 'MG\\Instamojo\\GiveWP\\Includes\\ModernGateway' === $name ) {
        throw new \Exception( 'Legacy path tried to autoload the PHP 7 modern adapter.' );
    }
} );
require __DIR__ . '/../src/Plugin.php';
$plugin = new Plugin();
$plugin->register();
if ( class_exists( 'MG\Instamojo\GiveWP\Includes\ModernGateway', false ) ) {
    throw new \Exception( 'Modern gateway was loaded on the legacy path.' );
}
class LegacyRegistrar { public function hasPaymentGateway( $id ) { return false; } public function registerGateway( $class ) { throw new \Exception( 'Modern gateway registered without GiveWP support.' ); } }
$registrar = new LegacyRegistrar();
call_user_func( $GLOBALS['hooks']['givewp_register_payment_gateway'], $registrar );
if ( class_exists( 'MG\Instamojo\GiveWP\Includes\ModernGateway', false ) ) {
    throw new \Exception( 'Modern gateway was parsed on the legacy path.' );
}
echo "Legacy loading checks passed.\n";
