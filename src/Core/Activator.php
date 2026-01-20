<?php
/**
 * Plugin Activator.
 *
 * @package IHumbak\Invoices\Core
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Core;

use IHumbak\Invoices\Modules\Portal\PortalController;

/**
 * Handles plugin activation.
 */
class Activator {

	/**
	 * Activate the plugin.
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Check requirements.
		if ( ! self::check_requirements() ) {
			return;
		}

		// Install database tables.
		$installer = new Installer();
		$installer->install();

		// Set default options.
		self::set_default_options();

		// Create upload directory.
		self::create_upload_directory();

		// Set activation flag for redirect.
		set_transient( 'ihumbak_invoices_activated', true, 30 );

		// Register customer portal endpoint and flush rewrite rules.
		$portal = new PortalController();
		$portal->register_endpoint();
		flush_rewrite_rules();

		// Clear any cached data.
		wp_cache_flush();
	}

	/**
	 * Check plugin requirements.
	 *
	 * @return bool
	 */
	private static function check_requirements(): bool {
		if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
			deactivate_plugins( plugin_basename( IHUMBAK_INVOICES_FILE ) );
			wp_die(
				esc_html__( 'iHumbak Invoices requires PHP 8.0 or higher.', 'ihumbak-invoices' ),
				esc_html__( 'Plugin Activation Error', 'ihumbak-invoices' ),
				array( 'back_link' => true )
			);
		}

		return true;
	}

	/**
	 * Set default plugin options.
	 *
	 * @return void
	 */
	private static function set_default_options(): void {
		$existing = get_option( 'ihumbak_invoices_settings' );

		if ( false === $existing ) {
			$defaults = array(
				'seller'    => array(
					'name'    => get_bloginfo( 'name' ),
					'details' => '',
				),
				'numbering' => array(
					'invoice_pattern'    => 'FV/{YYYY}/{MM}/{NNNN}',
					'receipt_pattern'    => 'PAR/{YYYY}/{MM}/{NNNN}',
					'correction_pattern' => 'FK/{YYYY}/{MM}/{NNNN}',
					'reset_monthly'      => true,
				),
				'pdf'       => array(
					'template'    => 'default',
					'logo_id'     => 0,
					'footer_text' => '',
				),
				'display'   => array(
					'show_order_column' => true,
					'nip_meta_key'      => '_billing_nip',
				),
			);

			add_option( 'ihumbak_invoices_settings', $defaults );
		}

		// Store plugin version.
		update_option( 'ihumbak_invoices_version', IHUMBAK_INVOICES_VERSION );
	}

	/**
	 * Create upload directory for PDF files.
	 *
	 * @return void
	 */
	private static function create_upload_directory(): void {
		$upload_dir   = wp_upload_dir();
		$invoices_dir = $upload_dir['basedir'] . '/ihumbak-invoices';

		if ( ! file_exists( $invoices_dir ) ) {
			wp_mkdir_p( $invoices_dir );

			// Initialize WP_Filesystem.
			global $wp_filesystem;

			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			WP_Filesystem();

			if ( $wp_filesystem ) {
				// Create .htaccess to protect PDF files.
				$htaccess_content = "Order deny,allow\nDeny from all\n";
				$wp_filesystem->put_contents( $invoices_dir . '/.htaccess', $htaccess_content, FS_CHMOD_FILE );

				// Create index.php to prevent directory listing.
				$wp_filesystem->put_contents( $invoices_dir . '/index.php', '<?php // Silence is golden.', FS_CHMOD_FILE );
			}
		}
	}
}
