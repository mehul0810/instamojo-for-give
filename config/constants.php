<?php
// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	wp_die( 'Cheating Huh?' );
}

// Define plugin version in SemVer format.
if ( ! defined( 'MG_INSTAMOJO_FOR_GIVE_VERSION' ) ) {
	define( 'MG_INSTAMOJO_FOR_GIVE_VERSION', '1.0.0' );
}

// Define plugin root File.
if ( ! defined( 'MG_INSTAMOJO_FOR_GIVE_PLUGIN_FILE' ) ) {
	define( 'MG_INSTAMOJO_FOR_GIVE_PLUGIN_FILE', dirname( dirname( __FILE__ ) ) . '/mg-instamojo-for-give.php' );
}

// Define plugin basename.
if ( ! defined( 'MG_INSTAMOJO_FOR_GIVE_PLUGIN_BASENAME' ) ) {
	define( 'MG_INSTAMOJO_FOR_GIVE_PLUGIN_BASENAME', plugin_basename( MG_INSTAMOJO_FOR_GIVE_PLUGIN_FILE ) );
}

// Define plugin directory Path.
if ( ! defined( 'MG_INSTAMOJO_FOR_GIVE_PLUGIN_DIR' ) ) {
	define( 'MG_INSTAMOJO_FOR_GIVE_PLUGIN_DIR', plugin_dir_path( MG_INSTAMOJO_FOR_GIVE_PLUGIN_FILE ) );
}

// Define plugin directory URL.
if ( ! defined( 'MG_INSTAMOJO_FOR_GIVE_PLUGIN_URL' ) ) {
	define( 'MG_INSTAMOJO_FOR_GIVE_PLUGIN_URL', plugin_dir_url( MG_INSTAMOJO_FOR_GIVE_PLUGIN_FILE ) );
}
