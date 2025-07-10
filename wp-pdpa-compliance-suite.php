<?php
/**
 * Plugin Name:       WP PDPA Compliance Suite
 * Plugin URI:        https://gustabe.com
 * Description:       An all-in-one solution to help WordPress & WooCommerce websites comply with Thailand's Personal Data Protection Act (PDPA).
 * Version:           1.0.1
 * Author:            Jirathip Jarungphan
 * Author URI:        https://gustabe.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-pdpa-cs
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define Constants
define( 'WPPCS_VERSION', '1.0.1' );
define( 'WPPCS_FILE', __FILE__ );
define( 'WPPCS_PATH', plugin_dir_path( WPPCS_FILE ) );
define( 'WPPCS_URL', plugin_dir_url( WPPCS_FILE ) );

/**
 * The function that runs when the plugin is activated.
 * This is the correct place to include files needed for activation.
 * * @since 1.0.1
 */
function wp_pdpa_cs_activate_plugin() {
	// We need the DB class to create tables
	require_once WPPCS_PATH . 'includes/class-db.php';
	// Call the installer
	\WP_PDPA_CS\DB::install();
}
// Register the activation hook to call our custom activation function.
register_activation_hook( WPPCS_FILE, 'wp_pdpa_cs_activate_plugin' );


// Include the main plugin class AFTER setting up activation.
require_once WPPCS_PATH . 'includes/class-main.php';

/**
 * The main function for running the plugin's day-to-day operations.
 *
 * @since 1.0.0
 */
function wp_pdpa_cs_run() {
	return \WP_PDPA_CS\Main::instance();
}

// Let's run the plugin (this happens on the 'plugins_loaded' hook inside the Main class)
wp_pdpa_cs_run();