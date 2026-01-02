<?php
/**
 * Numbering Service.
 *
 * @package IHumbak\Invoices\Modules\Invoice
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Invoice;

use IHumbak\Invoices\Core\Installer;

/**
 * Service for generating document numbers.
 */
class NumberingService {

	/**
	 * WordPress database instance.
	 *
	 * @var \wpdb
	 */
	private \wpdb $wpdb;

	/**
	 * Numbering table name.
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb  = $wpdb;
		$this->table = Installer::get_numbering_table();
	}

	/**
	 * Generate next document number.
	 *
	 * @param string $document_type Document type (invoice, receipt, correction).
	 * @param string $pattern       Numbering pattern.
	 * @param bool   $reset_monthly Whether to reset numbering monthly.
	 * @return string
	 */
	public function generateNumber( string $document_type, string $pattern, bool $reset_monthly = true ): string {
		$year  = (int) gmdate( 'Y' );
		$month = $reset_monthly ? (int) gmdate( 'n' ) : null;

		// Get and increment the counter.
		$next_number = $this->getNextNumber( $document_type, $year, $month, $pattern );

		// Replace placeholders in pattern.
		return $this->replacePlaceholders( $pattern, $next_number );
	}

	/**
	 * Get preview of next document number (without incrementing).
	 *
	 * @param string $document_type Document type.
	 * @param string $pattern       Numbering pattern.
	 * @param bool   $reset_monthly Whether to reset numbering monthly.
	 * @return string
	 */
	public function previewNextNumber( string $document_type, string $pattern, bool $reset_monthly = true ): string {
		$year  = (int) gmdate( 'Y' );
		$month = $reset_monthly ? (int) gmdate( 'n' ) : null;

		// Get current last number without incrementing.
		$last_number = $this->getLastNumber( $document_type, $year, $month );
		$next_number = $last_number + 1;

		return $this->replacePlaceholders( $pattern, $next_number );
	}

	/**
	 * Get next number and increment counter.
	 *
	 * Uses database locking to prevent race conditions when generating document numbers.
	 *
	 * @param string   $document_type Document type.
	 * @param int      $year          Year.
	 * @param int|null $month         Month (null if not resetting monthly).
	 * @param string   $pattern       Pattern.
	 * @return int
	 */
	private function getNextNumber( string $document_type, int $year, ?int $month, string $pattern ): int {
		// Use transient-based locking to prevent race conditions.
		$lock_key     = 'ihumbak_numbering_lock_' . $document_type . '_' . $year . '_' . ( $month ?? 'full' );
		$lock_timeout = 10; // Seconds.

		// Try to acquire lock with retries.
		$max_retries = 5;
		$retry_count = 0;

		while ( $retry_count < $max_retries ) {
			if ( false === get_transient( $lock_key ) ) {
				// Lock acquired.
				set_transient( $lock_key, time(), $lock_timeout );
				break;
			}
			++$retry_count;
			usleep( 100000 ); // Wait 100ms before retry.
		}

		if ( $retry_count >= $max_retries ) {
			// Could not acquire lock, delete stale lock and proceed.
			delete_transient( $lock_key );
			set_transient( $lock_key, time(), $lock_timeout );
		}

		try {
			// Build WHERE clause.
			$where = $this->wpdb->prepare(
				'document_type = %s AND year = %d',
				$document_type,
				$year
			);

			if ( null !== $month ) {
				$where .= $this->wpdb->prepare( ' AND month = %d', $month );
			} else {
				$where .= ' AND month IS NULL';
			}

			// Use SELECT ... FOR UPDATE for row-level locking (if supported).
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$row = $this->wpdb->get_row(
				"SELECT * FROM {$this->table} WHERE {$where} FOR UPDATE",
				ARRAY_A
			);

			if ( $row ) {
				// Increment existing counter atomically.
				$next_number = (int) $row['last_number'] + 1;

				$this->wpdb->update(
					$this->table,
					array( 'last_number' => $next_number ),
					array( 'id' => $row['id'] ),
					array( '%d' ),
					array( '%d' )
				);

				return $next_number;
			}

			// Create new counter starting at 1.
			$this->wpdb->insert(
				$this->table,
				array(
					'document_type' => $document_type,
					'year'          => $year,
					'month'         => $month,
					'last_number'   => 1,
					'pattern'       => $pattern,
				),
				array( '%s', '%d', '%d', '%d', '%s' )
			);

			return 1;
		} finally {
			// Always release the lock.
			delete_transient( $lock_key );
		}
	}

	/**
	 * Get last used number (without incrementing).
	 *
	 * @param string   $document_type Document type.
	 * @param int      $year          Year.
	 * @param int|null $month         Month.
	 * @return int
	 */
	private function getLastNumber( string $document_type, int $year, ?int $month ): int {
		$where = $this->wpdb->prepare(
			'document_type = %s AND year = %d',
			$document_type,
			$year
		);

		if ( null !== $month ) {
			$where .= $this->wpdb->prepare( ' AND month = %d', $month );
		} else {
			$where .= ' AND month IS NULL';
		}

		$last_number = $this->wpdb->get_var(
			"SELECT last_number FROM {$this->table} WHERE {$where}"
		);

		return (int) ( $last_number ?? 0 );
	}

	/**
	 * Replace placeholders in pattern.
	 *
	 * @param string $pattern Pattern with placeholders.
	 * @param int    $number  Document number.
	 * @return string
	 */
	private function replacePlaceholders( string $pattern, int $number ): string {
		$replacements = array(
			'{YYYY}' => gmdate( 'Y' ),
			'{YY}'   => gmdate( 'y' ),
			'{MM}'   => gmdate( 'm' ),
			'{DD}'   => gmdate( 'd' ),
			'{NNNN}' => str_pad( (string) $number, 4, '0', STR_PAD_LEFT ),
			'{NNN}'  => str_pad( (string) $number, 3, '0', STR_PAD_LEFT ),
			'{NN}'   => str_pad( (string) $number, 2, '0', STR_PAD_LEFT ),
			'{N}'    => (string) $number,
		);

		return str_replace(
			array_keys( $replacements ),
			array_values( $replacements ),
			$pattern
		);
	}

	/**
	 * Get default pattern for document type.
	 *
	 * @param string $document_type Document type.
	 * @return string
	 */
	public static function getDefaultPattern( string $document_type ): string {
		return match ( $document_type ) {
			'invoice'        => 'FV/{YYYY}/{MM}/{NNNN}',
			'receipt'        => 'PAR/{YYYY}/{MM}/{NNNN}',
			'credit_note'    => 'CN/{YYYY}/{MM}/{NNNN}',
			'receipt_return' => 'RR/{YYYY}/{MM}/{NNNN}',
			'correction'     => 'FK/{YYYY}/{MM}/{NNNN}', // Legacy - kept for backward compatibility.
			default          => 'DOC/{YYYY}/{MM}/{NNNN}',
		};
	}
}
