<?php
/**
 * RefundDataExtractor unit tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Modules\Invoice
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Modules\Invoice;

use IHumbak\Invoices\Modules\Invoice\RefundDataExtractor;
use PHPUnit\Framework\TestCase;

/**
 * Tests for RefundDataExtractor service.
 *
 * Note: Most methods require WooCommerce to be loaded, so we focus on testing
 * the behavior when WC is not available (edge cases) and structure validation.
 */
class RefundDataExtractorTest extends TestCase {

	/**
	 * System under test.
	 *
	 * @var RefundDataExtractor
	 */
	private RefundDataExtractor $extractor;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->extractor = new RefundDataExtractor();
	}

	/**
	 * Test class can be instantiated.
	 */
	public function test_can_be_instantiated(): void {
		$this->assertInstanceOf( RefundDataExtractor::class, $this->extractor );
	}

	/**
	 * Test extractRefundsFromOrderId returns empty array when WC not loaded.
	 */
	public function test_extract_refunds_from_order_id_returns_empty_when_wc_not_loaded(): void {
		// WC functions are not loaded in unit tests.
		$result = $this->extractor->extractRefundsFromOrderId( 123 );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test extractRefundsFromOrderId with zero ID returns empty array.
	 */
	public function test_extract_refunds_from_order_id_with_zero_returns_empty(): void {
		$result = $this->extractor->extractRefundsFromOrderId( 0 );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test extractRefundsFromOrderId with negative ID returns empty array.
	 */
	public function test_extract_refunds_from_order_id_with_negative_returns_empty(): void {
		$result = $this->extractor->extractRefundsFromOrderId( -5 );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test extractRefundById returns null when WC not loaded.
	 */
	public function test_extract_refund_by_id_returns_null_when_wc_not_loaded(): void {
		// WC functions are not loaded in unit tests.
		$result = $this->extractor->extractRefundById( 456 );

		$this->assertNull( $result );
	}

	/**
	 * Test extractRefundById with zero ID returns null.
	 */
	public function test_extract_refund_by_id_with_zero_returns_null(): void {
		$result = $this->extractor->extractRefundById( 0 );

		$this->assertNull( $result );
	}

	/**
	 * Test extractRefundById with negative ID returns null.
	 */
	public function test_extract_refund_by_id_with_negative_returns_null(): void {
		$result = $this->extractor->extractRefundById( -10 );

		$this->assertNull( $result );
	}

	/**
	 * Test extractRefundById with very large ID returns null.
	 */
	public function test_extract_refund_by_id_with_large_id_returns_null(): void {
		$result = $this->extractor->extractRefundById( 999999999 );

		$this->assertNull( $result );
	}

	/**
	 * Test extractRefundsFromOrderId with very large ID returns empty array.
	 */
	public function test_extract_refunds_from_order_id_with_large_id_returns_empty(): void {
		$result = $this->extractor->extractRefundsFromOrderId( 999999999 );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test multiple calls return consistent results.
	 */
	public function test_multiple_calls_return_consistent_results(): void {
		$result1 = $this->extractor->extractRefundsFromOrderId( 123 );
		$result2 = $this->extractor->extractRefundsFromOrderId( 123 );

		$this->assertEquals( $result1, $result2 );
	}

	/**
	 * Test different order IDs return empty arrays independently.
	 */
	public function test_different_order_ids_return_empty_arrays_independently(): void {
		$result1 = $this->extractor->extractRefundsFromOrderId( 100 );
		$result2 = $this->extractor->extractRefundsFromOrderId( 200 );
		$result3 = $this->extractor->extractRefundsFromOrderId( 300 );

		$this->assertEmpty( $result1 );
		$this->assertEmpty( $result2 );
		$this->assertEmpty( $result3 );
	}

	/**
	 * Test service is stateless between calls.
	 */
	public function test_service_is_stateless(): void {
		$extractor1 = new RefundDataExtractor();
		$extractor2 = new RefundDataExtractor();

		$result1 = $extractor1->extractRefundsFromOrderId( 123 );
		$result2 = $extractor2->extractRefundsFromOrderId( 123 );

		$this->assertEquals( $result1, $result2 );
	}
}
