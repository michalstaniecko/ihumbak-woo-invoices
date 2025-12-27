<?php
/**
 * CsvExporter unit tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Modules\Report
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Modules\Report;

use IHumbak\Invoices\Modules\Report\CsvExporter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CsvExporter.
 */
class CsvExporterTest extends TestCase {

	/**
	 * Exporter under test.
	 *
	 * @var CsvExporter
	 */
	private CsvExporter $exporter;

	/**
	 * Set up test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->exporter = new CsvExporter();
	}

	/**
	 * Test UTF-8 BOM constant is correct.
	 *
	 * @return void
	 */
	public function test_utf8_bom_constant_is_correct(): void {
		$expected_bom = "\xEF\xBB\xBF";
		$this->assertSame( $expected_bom, CsvExporter::UTF8_BOM );
	}

	/**
	 * Test delimiter constant is semicolon.
	 *
	 * @return void
	 */
	public function test_delimiter_is_semicolon(): void {
		$this->assertSame( ';', CsvExporter::DELIMITER );
	}

	/**
	 * Test generateFilename format with two-digit month.
	 *
	 * @return void
	 */
	public function test_generate_filename_format(): void {
		$filename = CsvExporter::generateFilename( 'invoice', 2025, 12 );
		$this->assertSame( 'report-invoice-2025-12.csv', $filename );
	}

	/**
	 * Test generateFilename pads single digit month.
	 *
	 * @return void
	 */
	public function test_generate_filename_with_single_digit_month(): void {
		$filename = CsvExporter::generateFilename( 'receipt', 2025, 1 );
		$this->assertSame( 'report-receipt-2025-01.csv', $filename );
	}

	/**
	 * Test generateFilename with credit_note type.
	 *
	 * @return void
	 */
	public function test_generate_filename_with_credit_note(): void {
		$filename = CsvExporter::generateFilename( 'credit_note', 2024, 6 );
		$this->assertSame( 'report-credit_note-2024-06.csv', $filename );
	}

	/**
	 * Test generateCsvContent starts with UTF-8 BOM.
	 *
	 * @return void
	 */
	public function test_generate_csv_content_starts_with_bom(): void {
		$report_data = array();
		$totals      = array(
			'document_count' => 0,
			'net_total'      => 0.0,
			'vat_total'      => 0.0,
			'gross_total'    => 0.0,
		);

		$content = $this->exporter->generateCsvContent( $report_data, $totals );

		$this->assertStringStartsWith( CsvExporter::UTF8_BOM, $content );
	}

	/**
	 * Test generateCsvContent includes headers.
	 *
	 * @return void
	 */
	public function test_generate_csv_content_includes_headers(): void {
		$report_data = array();
		$totals      = array(
			'document_count' => 0,
			'net_total'      => 0.0,
			'vat_total'      => 0.0,
			'gross_total'    => 0.0,
		);

		$content = $this->exporter->generateCsvContent( $report_data, $totals );

		// Remove BOM and check first line.
		$content_without_bom = substr( $content, 3 );
		$lines               = explode( "\r\n", $content_without_bom );

		// First line should contain headers (5 columns).
		$this->assertStringContainsString( ';', $lines[0] );
		$this->assertCount( 5, explode( ';', $lines[0] ) );
	}

	/**
	 * Test generateCsvContent includes data rows.
	 *
	 * @return void
	 */
	public function test_generate_csv_content_includes_data_rows(): void {
		$report_data = array(
			array(
				'payment_method_name' => 'Bank Transfer',
				'document_count'      => 5,
				'net_total'           => 1000.00,
				'vat_total'           => 230.00,
				'gross_total'         => 1230.00,
			),
		);
		$totals      = array(
			'document_count' => 5,
			'net_total'      => 1000.00,
			'vat_total'      => 230.00,
			'gross_total'    => 1230.00,
		);

		$content = $this->exporter->generateCsvContent( $report_data, $totals );

		$this->assertStringContainsString( 'Bank Transfer', $content );
		$this->assertStringContainsString( '1000,00', $content );
		$this->assertStringContainsString( '230,00', $content );
		$this->assertStringContainsString( '1230,00', $content );
	}

	/**
	 * Test generateCsvContent includes totals row.
	 *
	 * @return void
	 */
	public function test_generate_csv_content_includes_totals_row(): void {
		$report_data = array(
			array(
				'payment_method_name' => 'Credit Card',
				'document_count'      => 3,
				'net_total'           => 500.00,
				'vat_total'           => 115.00,
				'gross_total'         => 615.00,
			),
		);
		$totals      = array(
			'document_count' => 3,
			'net_total'      => 500.00,
			'vat_total'      => 115.00,
			'gross_total'    => 615.00,
		);

		$content = $this->exporter->generateCsvContent( $report_data, $totals );

		// TOTAL should be in the last data line.
		$this->assertStringContainsString( 'TOTAL', $content );
	}

	/**
	 * Test generateCsvContent formats numbers with comma decimal separator.
	 *
	 * @return void
	 */
	public function test_generate_csv_content_formats_numbers_with_comma(): void {
		$report_data = array(
			array(
				'payment_method_name' => 'Cash',
				'document_count'      => 1,
				'net_total'           => 123.45,
				'vat_total'           => 28.39,
				'gross_total'         => 151.84,
			),
		);
		$totals      = array(
			'document_count' => 1,
			'net_total'      => 123.45,
			'vat_total'      => 28.39,
			'gross_total'    => 151.84,
		);

		$content = $this->exporter->generateCsvContent( $report_data, $totals );

		$this->assertStringContainsString( '123,45', $content );
		$this->assertStringContainsString( '28,39', $content );
		$this->assertStringContainsString( '151,84', $content );
	}

	/**
	 * Test generateCsvContent uses CRLF line endings.
	 *
	 * @return void
	 */
	public function test_generate_csv_content_uses_crlf_line_endings(): void {
		$report_data = array();
		$totals      = array(
			'document_count' => 0,
			'net_total'      => 0.0,
			'vat_total'      => 0.0,
			'gross_total'    => 0.0,
		);

		$content = $this->exporter->generateCsvContent( $report_data, $totals );

		$this->assertStringContainsString( "\r\n", $content );
	}

	/**
	 * Test generateCsvContent quotes values.
	 *
	 * @return void
	 */
	public function test_generate_csv_content_quotes_values(): void {
		$report_data = array(
			array(
				'payment_method_name' => 'Test Method',
				'document_count'      => 1,
				'net_total'           => 100.00,
				'vat_total'           => 23.00,
				'gross_total'         => 123.00,
			),
		);
		$totals      = array(
			'document_count' => 1,
			'net_total'      => 100.00,
			'vat_total'      => 23.00,
			'gross_total'    => 123.00,
		);

		$content = $this->exporter->generateCsvContent( $report_data, $totals );

		$this->assertStringContainsString( '"Test Method"', $content );
	}

	/**
	 * Test generateCsvContent handles empty payment method name.
	 *
	 * @return void
	 */
	public function test_generate_csv_content_handles_empty_values(): void {
		$report_data = array(
			array(
				'payment_method_name' => '',
				'document_count'      => 2,
				'net_total'           => 200.00,
				'vat_total'           => 46.00,
				'gross_total'         => 246.00,
			),
		);
		$totals      = array(
			'document_count' => 2,
			'net_total'      => 200.00,
			'vat_total'      => 46.00,
			'gross_total'    => 246.00,
		);

		$content = $this->exporter->generateCsvContent( $report_data, $totals );

		// Should contain empty quoted string.
		$this->assertStringContainsString( '""', $content );
	}
}
