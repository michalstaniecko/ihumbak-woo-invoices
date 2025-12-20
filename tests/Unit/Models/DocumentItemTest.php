<?php
/**
 * DocumentItem unit tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Models
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Models;

use IHumbak\Invoices\Models\DocumentItem;
use PHPUnit\Framework\TestCase;

/**
 * Tests for DocumentItem model.
 */
class DocumentItemTest extends TestCase {

	/**
	 * Test default values.
	 */
	public function test_default_values(): void {
		$item = new DocumentItem();

		$this->assertNull( $item->getId() );
		$this->assertNull( $item->getDocumentId() );
		$this->assertNull( $item->getProductId() );
		$this->assertEquals( '', $item->getName() );
		$this->assertEquals( 1.0, $item->getQuantity() );
		$this->assertEquals( 'szt.', $item->getUnit() );
		$this->assertEquals( 0.0, $item->getUnitPriceNet() );
		$this->assertEquals( 0.0, $item->getUnitPriceGross() );
		$this->assertEquals( 23.0, $item->getTaxRate() );
		$this->assertEquals( 0.0, $item->getTaxAmount() );
		$this->assertEquals( 0.0, $item->getLineTotalNet() );
		$this->assertEquals( 0.0, $item->getLineTotalGross() );
	}

	/**
	 * Test fluent setters.
	 */
	public function test_fluent_setters(): void {
		$item = new DocumentItem();

		$result = $item->setId( 1 )
			->setDocumentId( 10 )
			->setProductId( 100 )
			->setName( 'Test Product' )
			->setQuantity( 2.5 )
			->setUnit( 'kg' )
			->setUnitPriceNet( 100.00 )
			->setUnitPriceGross( 123.00 )
			->setTaxRate( 23.0 )
			->setTaxAmount( 57.50 )
			->setLineTotalNet( 250.00 )
			->setLineTotalGross( 307.50 );

		$this->assertSame( $item, $result );
		$this->assertEquals( 1, $item->getId() );
		$this->assertEquals( 10, $item->getDocumentId() );
		$this->assertEquals( 100, $item->getProductId() );
		$this->assertEquals( 'Test Product', $item->getName() );
		$this->assertEquals( 2.5, $item->getQuantity() );
		$this->assertEquals( 'kg', $item->getUnit() );
		$this->assertEquals( 100.00, $item->getUnitPriceNet() );
		$this->assertEquals( 123.00, $item->getUnitPriceGross() );
		$this->assertEquals( 23.0, $item->getTaxRate() );
		$this->assertEquals( 57.50, $item->getTaxAmount() );
		$this->assertEquals( 250.00, $item->getLineTotalNet() );
		$this->assertEquals( 307.50, $item->getLineTotalGross() );
	}

	/**
	 * Test fromArray factory method.
	 */
	public function test_from_array(): void {
		$data = array(
			'id'               => 5,
			'document_id'      => 10,
			'product_id'       => 50,
			'name'             => 'Widget',
			'quantity'         => 3.0,
			'unit'             => 'pcs',
			'unit_price_net'   => 80.00,
			'unit_price_gross' => 98.40,
			'tax_rate'         => 23.0,
			'tax_amount'       => 55.20,
			'line_total_net'   => 240.00,
			'line_total_gross' => 295.20,
		);

		$item = DocumentItem::fromArray( $data );

		$this->assertEquals( 5, $item->getId() );
		$this->assertEquals( 10, $item->getDocumentId() );
		$this->assertEquals( 50, $item->getProductId() );
		$this->assertEquals( 'Widget', $item->getName() );
		$this->assertEquals( 3.0, $item->getQuantity() );
		$this->assertEquals( 'pcs', $item->getUnit() );
		$this->assertEquals( 80.00, $item->getUnitPriceNet() );
		$this->assertEquals( 98.40, $item->getUnitPriceGross() );
		$this->assertEquals( 23.0, $item->getTaxRate() );
		$this->assertEquals( 55.20, $item->getTaxAmount() );
		$this->assertEquals( 240.00, $item->getLineTotalNet() );
		$this->assertEquals( 295.20, $item->getLineTotalGross() );
	}

	/**
	 * Test fromArray with partial data uses defaults.
	 */
	public function test_from_array_with_defaults(): void {
		$data = array(
			'name' => 'Simple Product',
		);

		$item = DocumentItem::fromArray( $data );

		$this->assertEquals( 'Simple Product', $item->getName() );
		$this->assertEquals( 1.0, $item->getQuantity() );
		$this->assertEquals( 'szt.', $item->getUnit() );
		$this->assertEquals( 23.0, $item->getTaxRate() );
	}

	/**
	 * Test toArray method.
	 */
	public function test_to_array(): void {
		$item = new DocumentItem();
		$item->setId( 1 )
			->setDocumentId( 10 )
			->setProductId( 100 )
			->setName( 'Test' )
			->setQuantity( 2.0 )
			->setUnit( 'szt.' )
			->setUnitPriceNet( 100.00 )
			->setUnitPriceGross( 123.00 )
			->setTaxRate( 23.0 )
			->setTaxAmount( 46.00 )
			->setLineTotalNet( 200.00 )
			->setLineTotalGross( 246.00 );

		$array = $item->toArray();

		$this->assertEquals( 1, $array['id'] );
		$this->assertEquals( 10, $array['document_id'] );
		$this->assertEquals( 100, $array['product_id'] );
		$this->assertEquals( 'Test', $array['name'] );
		$this->assertEquals( 2.0, $array['quantity'] );
		$this->assertEquals( 'szt.', $array['unit'] );
		$this->assertEquals( 100.00, $array['unit_price_net'] );
		$this->assertEquals( 123.00, $array['unit_price_gross'] );
		$this->assertEquals( 23.0, $array['tax_rate'] );
		$this->assertEquals( 46.00, $array['tax_amount'] );
		$this->assertEquals( 200.00, $array['line_total_net'] );
		$this->assertEquals( 246.00, $array['line_total_gross'] );
	}

	/**
	 * Test round-trip fromArray -> toArray.
	 */
	public function test_round_trip(): void {
		$original = array(
			'id'               => 1,
			'document_id'      => 5,
			'product_id'       => 10,
			'name'             => 'Round Trip Product',
			'quantity'         => 1.5,
			'unit'             => 'kg',
			'unit_price_net'   => 50.00,
			'unit_price_gross' => 61.50,
			'tax_rate'         => 23.0,
			'tax_amount'       => 17.25,
			'line_total_net'   => 75.00,
			'line_total_gross' => 92.25,
		);

		$item   = DocumentItem::fromArray( $original );
		$result = $item->toArray();

		$this->assertEquals( $original, $result );
	}
}
