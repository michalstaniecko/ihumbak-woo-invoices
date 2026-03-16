<?php
/**
 * Plugin Name: iHumbak WooCommerce Invoices
 * Plugin URI: https://ihumbak.com/plugins/woo-invoices
 * Description: Generate VAT invoices, receipts and corrections for WooCommerce orders.
 * Version: 0.6.4
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: iHumbak
 * Author URI: https://ihumbak.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ihumbak-invoices
 * Domain Path: /languages
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 *
 * @package IHumbak\Invoices
 */

declare(strict_types=1);

namespace IHumbak\Invoices;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'IHUMBAK_INVOICES_VERSION', '0.6.4' );
define( 'IHUMBAK_INVOICES_FILE', __FILE__ );
define( 'IHUMBAK_INVOICES_PATH', plugin_dir_path( __FILE__ ) );
define( 'IHUMBAK_INVOICES_URL', plugin_dir_url( __FILE__ ) );
define( 'IHUMBAK_INVOICES_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader.
 */
if ( file_exists( IHUMBAK_INVOICES_PATH . 'vendor/autoload.php' ) ) {
	require_once IHUMBAK_INVOICES_PATH . 'vendor/autoload.php';
}

/**
 * Check plugin requirements.
 *
 * @return bool True if requirements are met.
 */
function ihumbak_invoices_check_requirements(): bool {
	$errors = array();

	// Check PHP version.
	if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
		$errors[] = sprintf(
			/* translators: %s: Required PHP version */
			__( 'iHumbak Invoices requires PHP %s or higher.', 'ihumbak-invoices' ),
			'8.0'
		);
	}

	// Check WordPress version.
	if ( version_compare( get_bloginfo( 'version' ), '6.0', '<' ) ) {
		$errors[] = sprintf(
			/* translators: %s: Required WordPress version */
			__( 'iHumbak Invoices requires WordPress %s or higher.', 'ihumbak-invoices' ),
			'6.0'
		);
	}

	// Check if WooCommerce is active.
	if ( ! class_exists( 'WooCommerce' ) ) {
		$errors[] = __( 'iHumbak Invoices requires WooCommerce to be installed and activated.', 'ihumbak-invoices' );
	} elseif ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '7.0', '<' ) ) {
		$errors[] = sprintf(
			/* translators: %s: Required WooCommerce version */
			__( 'iHumbak Invoices requires WooCommerce %s or higher.', 'ihumbak-invoices' ),
			'7.0'
		);
	}

	if ( ! empty( $errors ) ) {
		add_action(
			'admin_notices',
			function () use ( $errors ) {
				foreach ( $errors as $error ) {
					printf(
						'<div class="notice notice-error"><p>%s</p></div>',
						esc_html( $error )
					);
				}
			}
		);
		return false;
	}

	return true;
}

/**
 * Initialize the plugin.
 *
 * @return void
 */
function ihumbak_invoices_init(): void {
	// Check requirements after all plugins are loaded.
	if ( ! ihumbak_invoices_check_requirements() ) {
		return;
	}

	// Initialize the plugin.
	Core\Plugin::get_instance();
}

// Hook into plugins_loaded to ensure WooCommerce is available.
add_action( 'plugins_loaded', __NAMESPACE__ . '\ihumbak_invoices_init' );

// Activation hook.
register_activation_hook( __FILE__, array( Core\Activator::class, 'activate' ) );

// Deactivation hook.
register_deactivation_hook( __FILE__, array( Core\Deactivator::class, 'deactivate' ) );

// Declare HPOS compatibility.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);
