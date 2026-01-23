<?php
/**
 * Update Service.
 *
 * Handles automatic plugin updates from GitHub releases.
 *
 * @package IHumbak\Invoices\Modules\Updates
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Updates;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * Service for handling automatic plugin updates from GitHub.
 */
class UpdateService {

	/**
	 * Default GitHub repository URL.
	 */
	public const DEFAULT_REPOSITORY_URL = 'https://github.com/michalstaniecko/ihumbak-woo-invoices/';

	/**
	 * Plugin slug.
	 */
	public const PLUGIN_SLUG = 'ihumbak-invoices';

	/**
	 * Update checker instance.
	 *
	 * @var \YahnisElsts\PluginUpdateChecker\v5p6\Vcs\PluginUpdateChecker|\YahnisElsts\PluginUpdateChecker\v5p6\Plugin\UpdateChecker|\YahnisElsts\PluginUpdateChecker\v5p6\Theme\UpdateChecker|null
	 */
	private $update_checker = null;

	/**
	 * Check if updates are enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		// Check if disabled via constant.
		if ( defined( 'IHUMBAK_DISABLE_UPDATES' ) && IHUMBAK_DISABLE_UPDATES ) {
			return false;
		}

		/**
		 * Filter whether automatic updates are enabled.
		 *
		 * @since 0.6.0
		 *
		 * @param bool $enabled Whether updates are enabled. Default true.
		 */
		return (bool) apply_filters( 'ihumbak_updates_enabled', true );
	}

	/**
	 * Initialize the update checker.
	 *
	 * @return void
	 */
	public function init(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$repository_url = $this->get_repository_url();
		$plugin_file    = $this->get_plugin_file();

		$this->update_checker = PucFactory::buildUpdateChecker(
			$repository_url,
			$plugin_file,
			self::PLUGIN_SLUG
		);

		// Enable release assets for ZIP downloads.
		// The getVcsApi() returns GitHubApi when using GitHub URL, which has enableReleaseAssets().
		$api = $this->update_checker->getVcsApi();
		if ( method_exists( $api, 'enableReleaseAssets' ) ) {
			$api->enableReleaseAssets();
		}

		// Configure authentication if token is available.
		$token = $this->get_github_access_token();
		if ( ! empty( $token ) ) {
			$this->update_checker->setAuthentication( $token );
		}

		// Add filter for update info modification.
		$this->update_checker->addFilter(
			'request_info_result',
			array( $this, 'filter_update_info' )
		);
	}

	/**
	 * Force check for updates.
	 *
	 * @return object|null Update information or null if no update available.
	 */
	public function check_for_updates(): ?object {
		if ( ! $this->update_checker ) {
			return null;
		}

		return $this->update_checker->checkForUpdates();
	}

	/**
	 * Get the current plugin version.
	 *
	 * @return string
	 */
	public function get_current_version(): string {
		if ( defined( 'IHUMBAK_INVOICES_VERSION' ) ) {
			return IHUMBAK_INVOICES_VERSION;
		}

		return '0.0.0';
	}

	/**
	 * Get the repository URL.
	 *
	 * @return string
	 */
	public function get_repository_url(): string {
		/**
		 * Filter the GitHub repository URL.
		 *
		 * @since 0.6.0
		 *
		 * @param string $url Repository URL.
		 */
		return apply_filters( 'ihumbak_update_repository_url', self::DEFAULT_REPOSITORY_URL );
	}

	/**
	 * Get the GitHub access token.
	 *
	 * @return string
	 */
	public function get_github_access_token(): string {
		// Check for constant first.
		if ( defined( 'IHUMBAK_GITHUB_ACCESS_TOKEN' ) && is_string( IHUMBAK_GITHUB_ACCESS_TOKEN ) ) {
			return IHUMBAK_GITHUB_ACCESS_TOKEN;
		}

		/**
		 * Filter the GitHub access token for private repos or higher rate limits.
		 *
		 * @since 0.6.0
		 *
		 * @param string $token GitHub access token. Default empty.
		 */
		return apply_filters( 'ihumbak_github_access_token', '' );
	}

	/**
	 * Get the plugin main file path.
	 *
	 * @return string
	 */
	public function get_plugin_file(): string {
		if ( defined( 'IHUMBAK_INVOICES_FILE' ) ) {
			return IHUMBAK_INVOICES_FILE;
		}

		// Fallback to calculated path.
		return dirname( __DIR__, 3 ) . '/ihumbak-woo-invoices.php';
	}

	/**
	 * Filter update info before it's used.
	 *
	 * @param object|null $info Update info object.
	 * @return object|null Modified update info.
	 */
	public function filter_update_info( ?object $info ): ?object {
		if ( null === $info ) {
			return $info;
		}

		/**
		 * Filter the update info object.
		 *
		 * Allows modification of update information before it's displayed or used.
		 *
		 * @since 0.6.0
		 *
		 * @param object $info Update info object containing version, download URL, etc.
		 */
		return apply_filters( 'ihumbak_update_info', $info );
	}

	/**
	 * Get the update checker instance.
	 *
	 * @return \YahnisElsts\PluginUpdateChecker\v5p6\Vcs\PluginUpdateChecker|\YahnisElsts\PluginUpdateChecker\v5p6\Plugin\UpdateChecker|\YahnisElsts\PluginUpdateChecker\v5p6\Theme\UpdateChecker|null
	 */
	public function get_update_checker(): ?object {
		return $this->update_checker;
	}
}
