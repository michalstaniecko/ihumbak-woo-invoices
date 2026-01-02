<?php
/**
 * Report Service.
 *
 * @package IHumbak\Invoices\Modules\Report
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Report;

use IHumbak\Invoices\Core\Installer;

/**
 * Service for generating monthly reports.
 */
class ReportService {

	/**
	 * Allowed statuses for reporting.
	 *
	 * @var array<string>
	 */
	public const ALLOWED_STATUSES = array( 'issued', 'sent', 'paid' );

	/**
	 * Allowed document types.
	 *
	 * @var array<string>
	 */
	public const ALLOWED_DOCUMENT_TYPES = array( 'invoice', 'receipt', 'credit_note', 'receipt_return' );

	/**
	 * WordPress database instance.
	 *
	 * @var \wpdb
	 */
	private \wpdb $wpdb;

	/**
	 * Documents table name.
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
		$this->table = Installer::get_documents_table();
	}

	/**
	 * Get monthly report data aggregated by payment method.
	 *
	 * @param int    $year          Year (4 digits).
	 * @param int    $month         Month (1-12).
	 * @param string $document_type Document type (invoice, receipt, credit_note).
	 * @return array<int, array<string, mixed>> Report data rows.
	 */
	public function getMonthlyReport( int $year, int $month, string $document_type ): array {
		if ( ! $this->isValidDocumentType( $document_type ) ) {
			return array();
		}

		$unknown_label = __( 'Unknown', 'ihumbak-invoices' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$results = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT
					COALESCE(NULLIF(payment_method_title, ''), NULLIF(payment_method, ''), %s) AS payment_method_name,
					COUNT(*) AS document_count,
					SUM(subtotal) AS net_total,
					SUM(tax_total) AS vat_total,
					SUM(total) AS gross_total
				FROM {$this->table}
				WHERE document_type = %s
					AND YEAR(issue_date) = %d
					AND MONTH(issue_date) = %d
					AND status IN (%s, %s, %s)
				GROUP BY COALESCE(NULLIF(payment_method_title, ''), NULLIF(payment_method, ''), %s)
				ORDER BY gross_total DESC",
				$unknown_label,
				$document_type,
				$year,
				$month,
				self::ALLOWED_STATUSES[0],
				self::ALLOWED_STATUSES[1],
				self::ALLOWED_STATUSES[2],
				$unknown_label
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		if ( ! is_array( $results ) ) {
			return array();
		}

		// Ensure numeric types for calculations.
		return array_map(
			function ( array $row ): array {
				return array(
					'payment_method_name' => (string) $row['payment_method_name'],
					'document_count'      => (int) $row['document_count'],
					'net_total'           => (float) $row['net_total'],
					'vat_total'           => (float) $row['vat_total'],
					'gross_total'         => (float) $row['gross_total'],
				);
			},
			$results
		);
	}

	/**
	 * Calculate totals from report data.
	 *
	 * @param array<int, array<string, mixed>> $report_data Report data rows.
	 * @return array<string, mixed> Totals array.
	 */
	public function calculateTotals( array $report_data ): array {
		$totals = array(
			'document_count' => 0,
			'net_total'      => 0.0,
			'vat_total'      => 0.0,
			'gross_total'    => 0.0,
		);

		foreach ( $report_data as $row ) {
			$totals['document_count'] += (int) ( $row['document_count'] ?? 0 );
			$totals['net_total']      += (float) ( $row['net_total'] ?? 0.0 );
			$totals['vat_total']      += (float) ( $row['vat_total'] ?? 0.0 );
			$totals['gross_total']    += (float) ( $row['gross_total'] ?? 0.0 );
		}

		return $totals;
	}

	/**
	 * Get available years from documents.
	 *
	 * @return array<int> Array of years (newest first).
	 */
	public function getAvailableYears(): array {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $this->wpdb->get_col(
			"SELECT DISTINCT YEAR(issue_date) AS year
			FROM {$this->table}
			WHERE issue_date IS NOT NULL
			ORDER BY year DESC"
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$years = array_map( 'intval', $results );

		// Always include current year.
		$current_year = (int) gmdate( 'Y' );
		if ( ! in_array( $current_year, $years, true ) ) {
			array_unshift( $years, $current_year );
		}

		return $years;
	}

	/**
	 * Validate document type.
	 *
	 * @param string $type Document type.
	 * @return bool True if valid.
	 */
	public function isValidDocumentType( string $type ): bool {
		return in_array( $type, self::ALLOWED_DOCUMENT_TYPES, true );
	}

	/**
	 * Get month options for dropdown.
	 *
	 * @return array<int, string> Month number => Month name.
	 */
	public function getMonthOptions(): array {
		return array(
			1  => __( 'January', 'ihumbak-invoices' ),
			2  => __( 'February', 'ihumbak-invoices' ),
			3  => __( 'March', 'ihumbak-invoices' ),
			4  => __( 'April', 'ihumbak-invoices' ),
			5  => __( 'May', 'ihumbak-invoices' ),
			6  => __( 'June', 'ihumbak-invoices' ),
			7  => __( 'July', 'ihumbak-invoices' ),
			8  => __( 'August', 'ihumbak-invoices' ),
			9  => __( 'September', 'ihumbak-invoices' ),
			10 => __( 'October', 'ihumbak-invoices' ),
			11 => __( 'November', 'ihumbak-invoices' ),
			12 => __( 'December', 'ihumbak-invoices' ),
		);
	}

	/**
	 * Get document type options for dropdown.
	 *
	 * @return array<string, string> Type => Label.
	 */
	public function getDocumentTypeOptions(): array {
		return array(
			'invoice'        => __( 'Invoice', 'ihumbak-invoices' ),
			'receipt'        => __( 'Receipt', 'ihumbak-invoices' ),
			'credit_note'    => __( 'Credit Note', 'ihumbak-invoices' ),
			'receipt_return' => __( 'Receipt Return', 'ihumbak-invoices' ),
		);
	}
}
