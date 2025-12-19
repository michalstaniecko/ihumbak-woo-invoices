<?php
/**
 * Uninstall handler.
 *
 * Fired when the plugin is uninstalled.
 *
 * @package IHumbak\Invoices
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load autoloader.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

// Define constants if not defined.
if ( ! defined( 'IHUMBAK_INVOICES_PATH' ) ) {
	define( 'IHUMBAK_INVOICES_PATH', plugin_dir_path( __FILE__ ) );
}

use IHumbak\Invoices\Core\Installer;

/**
 * Perform plugin uninstallation.
 *
 * @return void
 */
function ihumbak_invoices_uninstall(): void {
	// Run database uninstall.
	$ihumbak_installer = new Installer();
	$ihumbak_installer->uninstall();

	// Delete upload directory.
	$ihumbak_upload_dir   = wp_upload_dir();
	$ihumbak_invoices_dir = $ihumbak_upload_dir['basedir'] . '/ihumbak-invoices';

	if ( is_dir( $ihumbak_invoices_dir ) ) {
		// Initialize WP_Filesystem.
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		if ( $wp_filesystem ) {
			// Remove directory recursively.
			$wp_filesystem->rmdir( $ihumbak_invoices_dir, true );
		}
	}

	// Clear any remaining transients and options.
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			'%ihumbak_invoices%'
		)
	);

	// Clear user meta.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
			'%ihumbak_invoices%'
		)
	);
}

// Run uninstall.
ihumbak_invoices_uninstall();
