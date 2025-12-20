<?php
/**
 * PdfCacheManager tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Modules\PDF
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Modules\PDF;

use IHumbak\Invoices\Modules\PDF\PdfCacheManager;
use IHumbak\Invoices\Models\Invoice;
use IHumbak\Invoices\Models\Receipt;
use IHumbak\Invoices\Models\Seller;
use PHPUnit\Framework\TestCase;

/**
 * Test PdfCacheManager class.
 */
class PdfCacheManagerTest extends TestCase {

	/**
	 * Cache manager instance.
	 *
	 * @var PdfCacheManager
	 */
	private PdfCacheManager $cache_manager;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->cache_manager = new PdfCacheManager();
	}

	/**
	 * Create a mock invoice for testing.
	 *
	 * @param string $number Document number.
	 * @return Invoice
	 */
	private function createMockInvoice( string $number = 'FV/2024/12/0001' ): Invoice {
		$invoice = new Invoice();
		$invoice->setDocumentNumber( $number );
		$invoice->setIssueDate( new \DateTimeImmutable( '2024-12-20' ) );
		$invoice->setSaleDate( new \DateTimeImmutable( '2024-12-20' ) );

		return $invoice;
	}

	/**
	 * Create a mock receipt for testing.
	 *
	 * @param string $number Document number.
	 * @return Receipt
	 */
	private function createMockReceipt( string $number = 'PAR/2024/12/0001' ): Receipt {
		$receipt = new Receipt();
		$receipt->setDocumentNumber( $number );
		$receipt->setIssueDate( new \DateTimeImmutable( '2024-12-20' ) );
		$receipt->setSaleDate( new \DateTimeImmutable( '2024-12-20' ) );

		return $receipt;
	}

	/**
	 * Test getBaseDir returns string.
	 */
	public function test_get_base_dir_returns_string(): void {
		$result = $this->cache_manager->getBaseDir();

		$this->assertIsString( $result );
		$this->assertStringContainsString( 'ihumbak-invoices', $result );
	}

	/**
	 * Test getBaseUrl returns string.
	 */
	public function test_get_base_url_returns_string(): void {
		$result = $this->cache_manager->getBaseUrl();

		$this->assertIsString( $result );
		$this->assertStringContainsString( 'ihumbak-invoices', $result );
	}

	/**
	 * Test generateFilename for invoice.
	 */
	public function test_generate_filename_for_invoice(): void {
		$invoice = $this->createMockInvoice( 'FV/2024/12/0001' );

		$result = $this->cache_manager->generateFilename( $invoice );

		$this->assertIsString( $result );
		$this->assertStringStartsWith( 'invoice-', $result );
		$this->assertStringEndsWith( '.pdf', $result );
	}

	/**
	 * Test generateFilename for receipt.
	 */
	public function test_generate_filename_for_receipt(): void {
		$receipt = $this->createMockReceipt( 'PAR/2024/12/0001' );

		$result = $this->cache_manager->generateFilename( $receipt );

		$this->assertIsString( $result );
		$this->assertStringStartsWith( 'receipt-', $result );
		$this->assertStringEndsWith( '.pdf', $result );
	}

	/**
	 * Test generateFilename sanitizes special characters.
	 */
	public function test_generate_filename_sanitizes_special_characters(): void {
		$invoice = $this->createMockInvoice( 'FV/2024/12/0001' );

		$result = $this->cache_manager->generateFilename( $invoice );

		// Should not contain slashes.
		$this->assertStringNotContainsString( '/', $result );
	}

	/**
	 * Test getCachePath includes year and month.
	 */
	public function test_get_cache_path_includes_year_and_month(): void {
		$invoice = $this->createMockInvoice();

		$result = $this->cache_manager->getCachePath( $invoice );

		$this->assertStringContainsString( '2024', $result );
		$this->assertStringContainsString( '12', $result );
	}

	/**
	 * Test getCacheUrl includes year and month.
	 */
	public function test_get_cache_url_includes_year_and_month(): void {
		$invoice = $this->createMockInvoice();

		$result = $this->cache_manager->getCacheUrl( $invoice );

		$this->assertStringContainsString( '2024', $result );
		$this->assertStringContainsString( '12', $result );
	}

	/**
	 * Test hasCachedPdf returns false for non-existent file.
	 */
	public function test_has_cached_pdf_returns_false_for_nonexistent(): void {
		$invoice = $this->createMockInvoice( 'FV/2024/12/9999' );

		$result = $this->cache_manager->hasCachedPdf( $invoice );

		$this->assertFalse( $result );
	}

	/**
	 * Test getCachedPdf returns null for non-existent file.
	 */
	public function test_get_cached_pdf_returns_null_for_nonexistent(): void {
		$invoice = $this->createMockInvoice( 'FV/2024/12/9999' );

		$result = $this->cache_manager->getCachedPdf( $invoice );

		$this->assertNull( $result );
	}

	/**
	 * Test deletePdf returns true for non-existent file.
	 */
	public function test_delete_pdf_returns_true_for_nonexistent(): void {
		$invoice = $this->createMockInvoice( 'FV/2024/12/9999' );

		$result = $this->cache_manager->deletePdf( $invoice );

		$this->assertTrue( $result );
	}

	/**
	 * Test getStats returns correct structure.
	 */
	public function test_get_stats_returns_correct_structure(): void {
		$result = $this->cache_manager->getStats();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'total_files', $result );
		$this->assertArrayHasKey( 'total_size', $result );
		$this->assertArrayHasKey( 'oldest_file', $result );
	}

	/**
	 * Test getStats returns integers.
	 */
	public function test_get_stats_returns_integers(): void {
		$result = $this->cache_manager->getStats();

		$this->assertIsInt( $result['total_files'] );
		$this->assertIsInt( $result['total_size'] );
	}

	/**
	 * Test clearOldCache returns integer.
	 */
	public function test_clear_old_cache_returns_integer(): void {
		$result = $this->cache_manager->clearOldCache();

		$this->assertIsInt( $result );
		$this->assertGreaterThanOrEqual( 0, $result );
	}

	/**
	 * Test ensureDirectoryExists returns boolean.
	 */
	public function test_ensure_directory_exists_returns_boolean(): void {
		$result = $this->cache_manager->ensureDirectoryExists();

		$this->assertIsBool( $result );
	}
}
