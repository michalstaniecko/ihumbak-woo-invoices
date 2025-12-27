<?php
/**
 * ReportService unit tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Modules\Report
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Modules\Report;

use IHumbak\Invoices\Modules\Report\ReportService;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ReportService.
 */
class ReportServiceTest extends TestCase {

	/**
	 * Test that allowed statuses contains issued.
	 *
	 * @return void
	 */
	public function test_allowed_statuses_contains_issued(): void {
		$this->assertContains( 'issued', ReportService::ALLOWED_STATUSES );
	}

	/**
	 * Test that allowed statuses contains sent.
	 *
	 * @return void
	 */
	public function test_allowed_statuses_contains_sent(): void {
		$this->assertContains( 'sent', ReportService::ALLOWED_STATUSES );
	}

	/**
	 * Test that allowed statuses contains paid.
	 *
	 * @return void
	 */
	public function test_allowed_statuses_contains_paid(): void {
		$this->assertContains( 'paid', ReportService::ALLOWED_STATUSES );
	}

	/**
	 * Test that allowed statuses excludes draft.
	 *
	 * @return void
	 */
	public function test_allowed_statuses_excludes_draft(): void {
		$this->assertNotContains( 'draft', ReportService::ALLOWED_STATUSES );
	}

	/**
	 * Test that allowed statuses excludes cancelled.
	 *
	 * @return void
	 */
	public function test_allowed_statuses_excludes_cancelled(): void {
		$this->assertNotContains( 'cancelled', ReportService::ALLOWED_STATUSES );
	}

	/**
	 * Test that allowed document types contains invoice.
	 *
	 * @return void
	 */
	public function test_allowed_document_types_contains_invoice(): void {
		$this->assertContains( 'invoice', ReportService::ALLOWED_DOCUMENT_TYPES );
	}

	/**
	 * Test that allowed document types contains receipt.
	 *
	 * @return void
	 */
	public function test_allowed_document_types_contains_receipt(): void {
		$this->assertContains( 'receipt', ReportService::ALLOWED_DOCUMENT_TYPES );
	}

	/**
	 * Test that allowed document types contains credit_note.
	 *
	 * @return void
	 */
	public function test_allowed_document_types_contains_credit_note(): void {
		$this->assertContains( 'credit_note', ReportService::ALLOWED_DOCUMENT_TYPES );
	}

	/**
	 * Test that calculate totals sums all values correctly.
	 *
	 * @return void
	 */
	public function test_calculate_totals_sums_all_values(): void {
		// Skip if wpdb is not available.
		if ( ! class_exists( 'wpdb' ) ) {
			$this->markTestSkipped( 'WordPress not available for this test.' );
		}

		$service = new ReportService();

		$report_data = array(
			array(
				'payment_method_name' => 'Bank Transfer',
				'document_count'      => 5,
				'net_total'           => 1000.00,
				'vat_total'           => 230.00,
				'gross_total'         => 1230.00,
			),
			array(
				'payment_method_name' => 'Credit Card',
				'document_count'      => 3,
				'net_total'           => 500.00,
				'vat_total'           => 115.00,
				'gross_total'         => 615.00,
			),
		);

		$totals = $service->calculateTotals( $report_data );

		$this->assertSame( 8, $totals['document_count'] );
		$this->assertSame( 1500.00, $totals['net_total'] );
		$this->assertSame( 345.00, $totals['vat_total'] );
		$this->assertSame( 1845.00, $totals['gross_total'] );
	}

	/**
	 * Test that calculate totals returns zero for empty data.
	 *
	 * @return void
	 */
	public function test_calculate_totals_returns_zero_for_empty_data(): void {
		// Skip if wpdb is not available.
		if ( ! class_exists( 'wpdb' ) ) {
			$this->markTestSkipped( 'WordPress not available for this test.' );
		}

		$service = new ReportService();
		$totals  = $service->calculateTotals( array() );

		$this->assertSame( 0, $totals['document_count'] );
		$this->assertSame( 0.0, $totals['net_total'] );
		$this->assertSame( 0.0, $totals['vat_total'] );
		$this->assertSame( 0.0, $totals['gross_total'] );
	}

	/**
	 * Test isValidDocumentType returns true for invoice.
	 *
	 * @return void
	 */
	public function test_is_valid_document_type_returns_true_for_invoice(): void {
		// Skip if wpdb is not available.
		if ( ! class_exists( 'wpdb' ) ) {
			$this->markTestSkipped( 'WordPress not available for this test.' );
		}

		$service = new ReportService();
		$this->assertTrue( $service->isValidDocumentType( 'invoice' ) );
	}

	/**
	 * Test isValidDocumentType returns true for receipt.
	 *
	 * @return void
	 */
	public function test_is_valid_document_type_returns_true_for_receipt(): void {
		// Skip if wpdb is not available.
		if ( ! class_exists( 'wpdb' ) ) {
			$this->markTestSkipped( 'WordPress not available for this test.' );
		}

		$service = new ReportService();
		$this->assertTrue( $service->isValidDocumentType( 'receipt' ) );
	}

	/**
	 * Test isValidDocumentType returns true for credit_note.
	 *
	 * @return void
	 */
	public function test_is_valid_document_type_returns_true_for_credit_note(): void {
		// Skip if wpdb is not available.
		if ( ! class_exists( 'wpdb' ) ) {
			$this->markTestSkipped( 'WordPress not available for this test.' );
		}

		$service = new ReportService();
		$this->assertTrue( $service->isValidDocumentType( 'credit_note' ) );
	}

	/**
	 * Test isValidDocumentType returns false for unknown type.
	 *
	 * @return void
	 */
	public function test_is_valid_document_type_returns_false_for_unknown(): void {
		// Skip if wpdb is not available.
		if ( ! class_exists( 'wpdb' ) ) {
			$this->markTestSkipped( 'WordPress not available for this test.' );
		}

		$service = new ReportService();
		$this->assertFalse( $service->isValidDocumentType( 'unknown' ) );
		$this->assertFalse( $service->isValidDocumentType( '' ) );
		$this->assertFalse( $service->isValidDocumentType( 'proforma' ) );
	}

	/**
	 * Test getMonthOptions returns all 12 months.
	 *
	 * @return void
	 */
	public function test_get_month_options_returns_all_months(): void {
		// Skip if wpdb is not available.
		if ( ! class_exists( 'wpdb' ) ) {
			$this->markTestSkipped( 'WordPress not available for this test.' );
		}

		$service = new ReportService();
		$months  = $service->getMonthOptions();

		$this->assertCount( 12, $months );
		$this->assertArrayHasKey( 1, $months );
		$this->assertArrayHasKey( 12, $months );
	}

	/**
	 * Test getDocumentTypeOptions returns all types.
	 *
	 * @return void
	 */
	public function test_get_document_type_options_returns_all_types(): void {
		// Skip if wpdb is not available.
		if ( ! class_exists( 'wpdb' ) ) {
			$this->markTestSkipped( 'WordPress not available for this test.' );
		}

		$service = new ReportService();
		$types   = $service->getDocumentTypeOptions();

		$this->assertCount( 3, $types );
		$this->assertArrayHasKey( 'invoice', $types );
		$this->assertArrayHasKey( 'receipt', $types );
		$this->assertArrayHasKey( 'credit_note', $types );
	}
}
