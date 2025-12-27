<?php
/**
 * CSV Exporter.
 *
 * @package IHumbak\Invoices\Modules\Report
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Report;

/**
 * Service for exporting reports to CSV.
 */
class CsvExporter {

	/**
	 * UTF-8 BOM for Excel compatibility.
	 *
	 * @var string
	 */
	public const UTF8_BOM = "\xEF\xBB\xBF";

	/**
	 * CSV delimiter (semicolon for European locales).
	 *
	 * @var string
	 */
	public const DELIMITER = ';';

	/**
	 * Export report data to CSV and trigger download.
	 *
	 * @param array<int, array<string, mixed>> $report_data Report data rows.
	 * @param array<string, mixed>             $totals      Totals row.
	 * @param string                           $filename    Output filename.
	 * @return void
	 */
	public function export( array $report_data, array $totals, string $filename ): void {
		$this->setDownloadHeaders( $filename );

		$content = $this->generateCsvContent( $report_data, $totals );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $content;
		exit;
	}

	/**
	 * Generate CSV content as string.
	 *
	 * @param array<int, array<string, mixed>> $report_data Report data rows.
	 * @param array<string, mixed>             $totals      Totals row.
	 * @return string CSV content.
	 */
	public function generateCsvContent( array $report_data, array $totals ): string {
		$output = self::UTF8_BOM;

		// Headers.
		$headers = array(
			__( 'Payment Method', 'ihumbak-invoices' ),
			__( 'Document Count', 'ihumbak-invoices' ),
			__( 'Net Total', 'ihumbak-invoices' ),
			__( 'VAT Total', 'ihumbak-invoices' ),
			__( 'Gross Total', 'ihumbak-invoices' ),
		);
		$output .= $this->arrayToCsvLine( $headers );

		// Data rows.
		foreach ( $report_data as $row ) {
			$line    = array(
				$row['payment_method_name'] ?? '',
				(string) ( $row['document_count'] ?? 0 ),
				$this->formatNumber( (float) ( $row['net_total'] ?? 0.0 ) ),
				$this->formatNumber( (float) ( $row['vat_total'] ?? 0.0 ) ),
				$this->formatNumber( (float) ( $row['gross_total'] ?? 0.0 ) ),
			);
			$output .= $this->arrayToCsvLine( $line );
		}

		// Totals row.
		$totals_line = array(
			__( 'TOTAL', 'ihumbak-invoices' ),
			(string) ( $totals['document_count'] ?? 0 ),
			$this->formatNumber( (float) ( $totals['net_total'] ?? 0.0 ) ),
			$this->formatNumber( (float) ( $totals['vat_total'] ?? 0.0 ) ),
			$this->formatNumber( (float) ( $totals['gross_total'] ?? 0.0 ) ),
		);
		$output     .= $this->arrayToCsvLine( $totals_line );

		return $output;
	}

	/**
	 * Generate filename for export.
	 *
	 * @param string $document_type Document type.
	 * @param int    $year          Year.
	 * @param int    $month         Month.
	 * @return string Filename.
	 */
	public static function generateFilename( string $document_type, int $year, int $month ): string {
		return sprintf(
			'report-%s-%04d-%02d.csv',
			sanitize_file_name( $document_type ),
			$year,
			$month
		);
	}

	/**
	 * Set headers for CSV download.
	 *
	 * @param string $filename Filename.
	 * @return void
	 */
	private function setDownloadHeaders( string $filename ): void {
		// Clean any existing output buffers.
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
	}

	/**
	 * Convert array to CSV line.
	 *
	 * @param array<string> $values Values to convert.
	 * @return string CSV line with line ending.
	 */
	private function arrayToCsvLine( array $values ): string {
		$escaped = array_map(
			function ( string $value ): string {
				// Escape quotes by doubling them.
				$escaped_value = str_replace( '"', '""', $value );
				return '"' . $escaped_value . '"';
			},
			$values
		);

		return implode( self::DELIMITER, $escaped ) . "\r\n";
	}

	/**
	 * Format number for CSV (2 decimal places with comma).
	 *
	 * @param float $value Value to format.
	 * @return string Formatted value.
	 */
	private function formatNumber( float $value ): string {
		return number_format( $value, 2, ',', '' );
	}
}
