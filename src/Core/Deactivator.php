<?php
/**
 * Plugin Deactivator.
 *
 * @package IHumbak\Invoices\Core
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Core;

/**
 * Handles plugin deactivation.
 */
class Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Clear scheduled events.
		self::clear_scheduled_events();

		// Clear transients.
		self::clear_transients();

		// Flush rewrite rules to remove portal endpoint.
		flush_rewrite_rules();

		// Clear cache.
		wp_cache_flush();
	}

	/**
	 * Clear scheduled cron events.
	 *
	 * @return void
	 */
	private static function clear_scheduled_events(): void {
		$events = array(
			'ihumbak_invoices_daily_cleanup',
			'ihumbak_invoices_send_reminders',
		);

		foreach ( $events as $event ) {
			$timestamp = wp_next_scheduled( $event );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $event );
			}
		}
	}

	/**
	 * Clear plugin transients.
	 *
	 * @return void
	 */
	private static function clear_transients(): void {
		global $wpdb;

		$transient_prefix         = '_transient_ihumbak_invoices_%';
		$transient_timeout_prefix = '_transient_timeout_ihumbak_invoices_%';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$transient_prefix,
				$transient_timeout_prefix
			)
		);
	}
}
