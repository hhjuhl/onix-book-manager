<?php
/**
 * Plugin Name: ONIX Book Manager for WooCommerce
 * Description: Add book product type, manage book metadata and export to ONIX 3.1 format.
 * Version: 0.1.1
 * Author: Hans Henrik Juhl
 * Author URI: https://kopula.dk
 * Text Domain: onix-book-manager
 * Domain Path: /languages
 * Requires at least: 6.8
 * Tested up to: 6.9.1
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
 * WC requires at least: 10.5.0
 * WC tested up to: 10.5.0
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Define Plugin Constants
 */
define( 'OBM_VERSION', '0.1.1' );
define( 'OBM_PATH', plugin_dir_path( __FILE__ ) );
define( 'OBM_URL', plugin_dir_url( __FILE__ ) );

/**
 * 1. Load Translations
 * This looks for .mo files in the /languages/ folder.
 * Example: /languages/onix-book-manager-da_DK.mo
 */
add_action( 'init', 'obm_load_textdomain' );
function obm_load_textdomain() {
	load_plugin_textdomain(
		'onix-book-manager',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}

/**
 * 2. Initialize Plugin Components
 * We wrap this in 'plugins_loaded' to ensure WooCommerce is ready first.
 */
add_action( 'plugins_loaded', 'obm_init_plugin' );
function obm_init_plugin() {

	// Only run if WooCommerce is active.
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	// Load all plugin components.
	$includes = array(
		'class-obm-product.php',        // Core 'book' product type registration.
		'class-obm-product-admin.php',  // Admin-facing UI for the book product type.
		'class-obm-product-frontend.php', // Frontend display for the book product type.
		'class-obm-thema.php',          // Thema subject code handler.
		'class-obm-admin.php',          // General plugin admin pages and settings.
		'class-obm-export.php',         // ONIX XML export logic.
	);

	foreach ( $includes as $file ) {
		$path = OBM_PATH . 'includes/' . $file;
		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
}

/**
 * 3. Activation Hook (Optional)
 * Useful if you want to set default settings upon plugin activation.
 */
register_activation_hook( __FILE__, 'obm_activate_plugin' );
function obm_activate_plugin() {
	// Logic for first-time setup goes here.
}
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);
		}
	}
);
