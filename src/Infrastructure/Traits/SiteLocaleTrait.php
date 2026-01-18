<?php
/**
 * Site Locale Trait.
 *
 * Provides shared locale switching functionality for PDF generation and email sending.
 *
 * @package IHumbak\Invoices\Infrastructure\Traits
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Infrastructure\Traits;

/**
 * Trait for switching to site locale.
 *
 * When admin has a different language than the site (e.g., admin: EN, site: NO),
 * content should be generated in the site's language, not the admin's.
 */
trait SiteLocaleTrait {

	/**
	 * Target locale for content generation.
	 *
	 * @var string|null
	 */
	private ?string $target_locale = null;

	/**
	 * Original locale before switching.
	 *
	 * @var string|null
	 */
	private ?string $original_locale = null;

	/**
	 * Switch to site locale for content generation.
	 *
	 * @param string $filter_hook Optional filter hook to allow locale override.
	 * @return bool True if locale was switched, false otherwise.
	 */
	protected function switchToSiteLocale( string $filter_hook = '' ): bool {
		// Save original locale for restoration later.
		$this->original_locale = determine_locale();

		// Get site locale from WordPress options (not affected by admin user's language preference).
		// In admin context, get_locale() may return the admin user's language, not the site language.
		$site_locale = $this->getSiteLocale();

		// Apply filter if provided, allowing locale override.
		if ( ! empty( $filter_hook ) ) {
			/**
			 * Filter the locale used for content generation.
			 *
			 * @param string $locale The locale to use. Default is site locale.
			 */
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Hook name is passed by caller with proper prefix.
			$this->target_locale = apply_filters( $filter_hook, $site_locale );
		} else {
			$this->target_locale = $site_locale;
		}

		// Check if we need to switch locale.
		if ( $this->target_locale === $this->original_locale ) {
			// No switch needed - already using the correct locale.
			// Textdomains should already be loaded for this locale.
			return false;
		}

		// Switch to the target locale.
		$switched = switch_to_locale( $this->target_locale );

		// Always reload textdomains, even if switch_to_locale() returns false.
		// This ensures we load correct translation files for the target locale.
		$this->reloadTextdomains();

		return $switched;
	}

	/**
	 * Get the site locale regardless of admin user's language preference.
	 *
	 * WordPress may switch locale in admin context based on user's profile language setting.
	 * This method returns the actual site locale from the WPLANG option.
	 *
	 * @return string Site locale (e.g., 'nb_NO', 'en_US').
	 */
	protected function getSiteLocale(): string {
		// WPLANG option stores the site language setting.
		// Empty value means English (en_US).
		$site_locale = get_option( 'WPLANG' );

		if ( empty( $site_locale ) ) {
			return 'en_US';
		}

		return $site_locale;
	}

	/**
	 * Restore the previous locale after content generation.
	 *
	 * @return void
	 */
	protected function restoreLocale(): void {
		restore_previous_locale();

		// Set target_locale to original locale for textdomain restoration.
		$this->target_locale = $this->original_locale;
		$this->reloadTextdomains();

		// Clear locale state.
		$this->target_locale   = null;
		$this->original_locale = null;
	}

	/**
	 * Reload textdomains for target locale.
	 *
	 * Uses direct load_textdomain() with explicit locale to bypass WordPress
	 * determine_locale() which may return admin user's locale in admin context.
	 *
	 * @return void
	 */
	protected function reloadTextdomains(): void {
		$locale = $this->target_locale;

		if ( empty( $locale ) ) {
			return;
		}

		// Reload plugin textdomain with explicit locale path.
		unload_textdomain( 'ihumbak-invoices' );

		// Try plugin languages directory first.
		$plugin_mo_file = IHUMBAK_INVOICES_PATH . 'languages/ihumbak-invoices-' . $locale . '.mo';
		$global_mo_file = WP_LANG_DIR . '/plugins/ihumbak-invoices-' . $locale . '.mo';

		if ( file_exists( $plugin_mo_file ) ) {
			load_textdomain( 'ihumbak-invoices', $plugin_mo_file );
		} elseif ( file_exists( $global_mo_file ) ) {
			load_textdomain( 'ihumbak-invoices', $global_mo_file );
		} elseif ( 'en_US' !== $locale ) {
			// Log warning only for non-English locales (English doesn't need .mo file).
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(
				sprintf(
					'[iHumbak Invoices] Translation file not found for locale "%s". Checked: %s, %s',
					$locale,
					$plugin_mo_file,
					$global_mo_file
				)
			);
		}

		// Also reload WooCommerce textdomain for currency/payment translations.
		if ( defined( 'WC_PLUGIN_FILE' ) ) {
			unload_textdomain( 'woocommerce' );

			// WooCommerce translation file paths.
			$wc_plugin_mo = dirname( WC_PLUGIN_FILE ) . '/i18n/languages/woocommerce-' . $locale . '.mo';
			$wc_global_mo = WP_LANG_DIR . '/plugins/woocommerce-' . $locale . '.mo';

			if ( file_exists( $wc_global_mo ) ) {
				load_textdomain( 'woocommerce', $wc_global_mo );
			} elseif ( file_exists( $wc_plugin_mo ) ) {
				load_textdomain( 'woocommerce', $wc_plugin_mo );
			}
		}
	}
}
