<?php
// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin version in SemVer format.
if ( ! defined( 'INSTAMOJO_FOR_GIVE_VERSION' ) ) {
	define( 'INSTAMOJO_FOR_GIVE_VERSION', '1.0.0' );
}

// Define plugin root File.
if ( ! defined( 'INSTAMOJO_FOR_GIVE_PLUGIN_FILE' ) ) {
	define( 'INSTAMOJO_FOR_GIVE_PLUGIN_FILE', dirname( dirname( __FILE__ ) ) . '/instamojo-for-give.php' );
}

// Define plugin basename.
if ( ! defined( 'INSTAMOJO_FOR_GIVE_PLUGIN_BASENAME' ) ) {
	define( 'INSTAMOJO_FOR_GIVE_PLUGIN_BASENAME', plugin_basename( INSTAMOJO_FOR_GIVE_PLUGIN_FILE ) );
}

// Define plugin directory Path.
if ( ! defined( 'INSTAMOJO_FOR_GIVE_PLUGIN_DIR' ) ) {
	define( 'INSTAMOJO_FOR_GIVE_PLUGIN_DIR', plugin_dir_path( INSTAMOJO_FOR_GIVE_PLUGIN_FILE ) );
}

// Define plugin directory URL.
if ( ! defined( 'INSTAMOJO_FOR_GIVE_PLUGIN_URL' ) ) {
	define( 'INSTAMOJO_FOR_GIVE_PLUGIN_URL', plugin_dir_url( INSTAMOJO_FOR_GIVE_PLUGIN_FILE ) );
}
