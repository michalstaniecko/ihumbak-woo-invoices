<?php
/**
 * CalculationService unit tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Modules\Invoice
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Modules\Invoice;

use IHumbak\Invoices\Modules\Invoice\CalculationService;
use IHumbak\Invoices\Models\DocumentItem;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CalculationService.
 */
class CalculationServiceTest extends TestCase {

	/**
	 * Service under test.
	 *
	 * @var CalculationService
	 */
	private CalculationService $service;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->service = new CalculationService();
	}

	/**
	 * Test calculating from net price with 23% VAT.
	 */
	public function test_calculate_from_net_with_23_percent_vat(): void {
		$result = $this->service->calculateFromNet( 100.00, 1, 23 );

		$this->assertEquals( 100.00, $result['unit_price_net'] );
		$this->assertEquals( 123.00, $result['unit_price_gross'] );
		$this->assertEquals( 23.00, $result['tax_rate'] );
		$this->assertEquals( 23.00, $result['tax_amount'] );
		$this->assertEquals( 100.00, $result['line_total_net'] );
		$this->assertEquals( 123.00, $result['line_total_gross'] );
	}

	/**
	 * Test calculating from net price with quantity.
	 */
	public function test_calculate_from_net_with_quantity(): void {
		$result = $this->service->calculateFromNet( 50.00, 3, 23 );

		$this->assertEquals( 50.00, $result['unit_price_net'] );
		$this->assertEquals( 61.50, $result['unit_price_gross'] );
		$this->assertEquals( 150.00, $result['line_total_net'] );
		$this->assertEquals( 34.50, $result['tax_amount'] );
		$this->assertEquals( 184.50, $result['line_total_gross'] );
	}

	/**
	 * Test calculating from net price with 8% VAT.
	 */
	public function test_calculate_from_net_with_8_percent_vat(): void {
		$result = $this->service->calculateFromNet( 100.00, 1, 8 );

		$this->assertEquals( 100.00, $result['unit_price_net'] );
		$this->assertEquals( 108.00, $result['unit_price_gross'] );
		$this->assertEquals( 8.00, $result['tax_amount'] );
		$this->assertEquals( 108.00, $result['line_total_gross'] );
	}

	/**
	 * Test calculating from net price with 0% VAT.
	 */
	public function test_calculate_from_net_with_zero_vat(): void {
		$result = $this->service->calculateFromNet( 100.00, 1, 0 );

		$this->assertEquals( 100.00, $result['unit_price_net'] );
		$this->assertEquals( 100.00, $result['unit_price_gross'] );
		$this->assertEquals( 0.00, $result['tax_amount'] );
		$this->assertEquals( 100.00, $result['line_total_gross'] );
	}

	/**
	 * Test calculating from gross price with 23% VAT.
	 */
	public function test_calculate_from_gross_with_23_percent_vat(): void {
		$result = $this->service->calculateFromGross( 123.00, 1, 23 );

		$this->assertEquals( 100.00, $result['unit_price_net'] );
		$this->assertEquals( 123.00, $result['unit_price_gross'] );
		$this->assertEquals( 23.00, $result['tax_amount'] );
		$this->assertEquals( 100.00, $result['line_total_net'] );
		$this->assertEquals( 123.00, $result['line_total_gross'] );
	}

	/**
	 * Test calculating from gross price with quantity.
	 */
	public function test_calculate_from_gross_with_quantity(): void {
		$result = $this->service->calculateFromGross( 61.50, 2, 23 );

		$this->assertEquals( 50.00, $result['unit_price_net'] );
		$this->assertEquals( 61.50, $result['unit_price_gross'] );
		$this->assertEquals( 123.00, $result['line_total_gross'] );
		$this->assertEquals( 100.00, $result['line_total_net'] );
		$this->assertEquals( 23.00, $result['tax_amount'] );
	}

	/**
	 * Test calculating document totals from items.
	 */
	public function test_calculate_totals(): void {
		$item1 = new DocumentItem();
		$item1->setLineTotalNet( 100.00 );
		$item1->setTaxAmount( 23.00 );
		$item1->setLineTotalGross( 123.00 );

		$item2 = new DocumentItem();
		$item2->setLineTotalNet( 200.00 );
		$item2->setTaxAmount( 46.00 );
		$item2->setLineTotalGross( 246.00 );

		$result = $this->service->calculateTotals( array( $item1, $item2 ) );

		$this->assertEquals( 300.00, $result['subtotal'] );
		$this->assertEquals( 69.00, $result['tax_total'] );
		$this->assertEquals( 369.00, $result['total'] );
	}

	/**
	 * Test calculating VAT breakdown by rate.
	 */
	public function test_calculate_vat_breakdown(): void {
		$item1 = new DocumentItem();
		$item1->setTaxRate( 23 );
		$item1->setLineTotalNet( 100.00 );
		$item1->setTaxAmount( 23.00 );
		$item1->setLineTotalGross( 123.00 );

		$item2 = new DocumentItem();
		$item2->setTaxRate( 23 );
		$item2->setLineTotalNet( 50.00 );
		$item2->setTaxAmount( 11.50 );
		$item2->setLineTotalGross( 61.50 );

		$item3 = new DocumentItem();
		$item3->setTaxRate( 8 );
		$item3->setLineTotalNet( 100.00 );
		$item3->setTaxAmount( 8.00 );
		$item3->setLineTotalGross( 108.00 );

		$result = $this->service->calculateVatBreakdown( array( $item1, $item2, $item3 ) );

		$this->assertArrayHasKey( '23', $result );
		$this->assertArrayHasKey( '8', $result );

		// 23% rate totals
		$this->assertEquals( 23, $result['23']['rate'] );
		$this->assertEquals( 150.00, $result['23']['net_total'] );
		$this->assertEquals( 34.50, $result['23']['tax_amount'] );
		$this->assertEquals( 184.50, $result['23']['gross_total'] );

		// 8% rate totals
		$this->assertEquals( 8, $result['8']['rate'] );
		$this->assertEquals( 100.00, $result['8']['net_total'] );
		$this->assertEquals( 8.00, $result['8']['tax_amount'] );
		$this->assertEquals( 108.00, $result['8']['gross_total'] );
	}

	/**
	 * Test calculating from items data.
	 */
	public function test_calculate_from_items_data(): void {
		$items_data = array(
			array(
				'name'           => 'Product 1',
				'quantity'       => 2,
				'unit_price_net' => 100.00,
				'tax_rate'       => 23,
				'price_type'     => 'net',
			),
			array(
				'name'           => 'Product 2',
				'quantity'       => 1,
				'unit_price_net' => 50.00,
				'tax_rate'       => 8,
				'price_type'     => 'net',
			),
		);

		$result = $this->service->calculateFromItemsData( $items_data );

		$this->assertEquals( 250.00, $result['subtotal'] );
		$this->assertEquals( 50.00, $result['tax_total'] ); // 46 + 4
		$this->assertEquals( 300.00, $result['total'] ); // 246 + 54
	}

	/**
	 * Test money formatting.
	 */
	public function test_format_money(): void {
		$this->assertEquals( '1 234,56 PLN', CalculationService::formatMoney( 1234.56 ) );
		$this->assertEquals( '100,00 PLN', CalculationService::formatMoney( 100.00 ) );
		$this->assertEquals( '100,00 EUR', CalculationService::formatMoney( 100.00, 'EUR' ) );
	}

	/**
	 * Test getting tax rates.
	 */
	public function test_get_tax_rates(): void {
		$rates = CalculationService::getTaxRates();

		$this->assertArrayHasKey( 23, $rates );
		$this->assertArrayHasKey( 8, $rates );
		$this->assertArrayHasKey( 5, $rates );
		$this->assertArrayHasKey( 0, $rates );
		$this->assertArrayHasKey( 'zw', $rates );
		$this->assertArrayHasKey( 'np', $rates );
	}

	/**
	 * Test rounding precision.
	 */
	public function test_rounding_precision(): void {
		// Test that results are rounded to 2 decimal places
		$result = $this->service->calculateFromNet( 33.33, 3, 23 );

		$this->assertEquals( 99.99, $result['line_total_net'] );
		$this->assertIsFloat( $result['line_total_net'] );
		$this->assertIsFloat( $result['line_total_gross'] );

		// Verify exact rounding behavior
		$result2 = $this->service->calculateFromNet( 10.005, 1, 23 );
		$this->assertEquals( 10.01, $result2['line_total_net'] );
	}
}
