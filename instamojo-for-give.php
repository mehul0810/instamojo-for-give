<?php
/**
* Instamojo for Give - WordPress Plugin
*
* @package           Instamojo for Give
* @author            Mehul Gohil
* @copyright         2021 Mehul Gohil <hello@mehulgohil.com>
* @license           GPL-2.0-or-later
*
* @wordpress-plugin
*
* Plugin Name:       Instamojo for Give
* Plugin URI:        https://wordpress.org/plugins/instamojo-for-give
* Description:       Accept donations via Instamojo payment gateway using GiveWP.
* Version:           1.0.0
* Requires at least: 4.8
* Requires PHP:      5.6
* Author:            Mehul Gohil
* Author URI:        https://mehulgohil.com
* Text Domain:       instamojo-for-give
* License:           GPL v2 or later
* License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
*/

namespace Instamojo\GiveWP;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	wp_die( 'Cheating Huh?' );
}

// Load Constants.
require_once __DIR__ . '/config/constants.php';

// Automatically loads files used throughout the plugin.
require_once 'vendor/autoload.php';

// Initialize the plugin.
$plugin = new Plugin();
$plugin->register();
