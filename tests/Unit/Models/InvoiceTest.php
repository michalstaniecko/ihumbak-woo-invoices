<?php
/**
 * Invoice unit tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Models
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Models;

use IHumbak\Invoices\Models\Invoice;
use IHumbak\Invoices\Models\Document;
use IHumbak\Invoices\Models\Buyer;
use IHumbak\Invoices\Models\Seller;
use IHumbak\Invoices\Models\DocumentItem;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Invoice model.
 */
class InvoiceTest extends TestCase {

	/**
	 * Test invoice extends Document.
	 */
	public function test_extends_document(): void {
		$invoice = new Invoice();
		$this->assertInstanceOf( Document::class, $invoice );
	}

	/**
	 * Test document type.
	 */
	public function test_document_type(): void {
		$invoice = new Invoice();
		$this->assertEquals( 'invoice', $invoice->getDocumentType() );
		$this->assertEquals( Invoice::TYPE, $invoice->getDocumentType() );
	}

	/**
	 * Test document type label.
	 */
	public function test_document_type_label(): void {
		$invoice = new Invoice();
		$this->assertEquals( 'VAT Invoice', $invoice->getDocumentTypeLabel() );
	}

	/**
	 * Test default status.
	 */
	public function test_default_status(): void {
		$invoice = new Invoice();
		$this->assertEquals( Document::STATUS_DRAFT, $invoice->getStatus() );
		$this->assertTrue( $invoice->isDraft() );
	}

	/**
	 * Test status transitions.
	 */
	public function test_status_transitions(): void {
		$invoice = new Invoice();

		$invoice->setStatus( Document::STATUS_ISSUED );
		$this->assertTrue( $invoice->isIssued() );
		$this->assertFalse( $invoice->isDraft() );
		$this->assertFalse( $invoice->canBeEdited() );

		$invoice->setStatus( Document::STATUS_CANCELLED );
		$this->assertTrue( $invoice->isCancelled() );
	}

	/**
	 * Test invalid status throws exception.
	 */
	public function test_invalid_status_throws_exception(): void {
		$invoice = new Invoice();

		$this->expectException( \InvalidArgumentException::class );
		$invoice->setStatus( 'invalid_status' );
	}

	/**
	 * Test payment method.
	 */
	public function test_payment_method(): void {
		$invoice = new Invoice();

		$invoice->setPaymentMethod( 'transfer' );
		$this->assertEquals( 'transfer', $invoice->getPaymentMethod() );
	}

	/**
	 * Test get payment methods.
	 */
	public function test_get_payment_methods(): void {
		$methods = Invoice::getPaymentMethods();

		$this->assertArrayHasKey( 'transfer', $methods );
		$this->assertArrayHasKey( 'cash', $methods );
		$this->assertArrayHasKey( 'card', $methods );
		$this->assertArrayHasKey( 'online', $methods );
	}

	/**
	 * Test fluent setters.
	 */
	public function test_fluent_setters(): void {
		$invoice = new Invoice();
		$date    = new \DateTimeImmutable( '2025-01-15' );
		$buyer   = new Buyer( name: 'Test Buyer' );
		$seller  = new Seller( name: 'Test Seller', details: 'NIP: 1234567890' );

		$result = $invoice
			->setId( 1 )
			->setOrderId( 100 )
			->setDocumentNumber( 'FV/2025/01/0001' )
			->setIssueDate( $date )
			->setSaleDate( $date )
			->setDueDate( $date )
			->setBuyer( $buyer )
			->setSeller( $seller )
			->setSubtotal( 1000.00 )
			->setTaxTotal( 230.00 )
			->setTotal( 1230.00 )
			->setCurrency( 'PLN' )
			->setNotes( 'Test notes' )
			->setPaymentMethod( 'transfer' );

		$this->assertSame( $invoice, $result );
		$this->assertEquals( 1, $invoice->getId() );
		$this->assertEquals( 100, $invoice->getOrderId() );
		$this->assertEquals( 'FV/2025/01/0001', $invoice->getDocumentNumber() );
		$this->assertEquals( $date, $invoice->getIssueDate() );
		$this->assertEquals( $buyer, $invoice->getBuyer() );
		$this->assertEquals( $seller, $invoice->getSeller() );
		$this->assertEquals( 1000.00, $invoice->getSubtotal() );
		$this->assertEquals( 230.00, $invoice->getTaxTotal() );
		$this->assertEquals( 1230.00, $invoice->getTotal() );
	}

	/**
	 * Test items management.
	 */
	public function test_items_management(): void {
		$invoice = new Invoice();
		$item1   = new DocumentItem();
		$item1->setName( 'Item 1' );
		$item2 = new DocumentItem();
		$item2->setName( 'Item 2' );

		$invoice->addItem( $item1 );
		$invoice->addItem( $item2 );

		$items = $invoice->getItems();
		$this->assertCount( 2, $items );
		$this->assertSame( $item1, $items[0] );
		$this->assertSame( $item2, $items[1] );

		// Test setItems
		$invoice->setItems( array( $item1 ) );
		$this->assertCount( 1, $invoice->getItems() );
	}

	/**
	 * Test fromArray factory method.
	 */
	public function test_from_array(): void {
		$data = array(
			'id'              => 5,
			'order_id'        => 100,
			'document_number' => 'FV/2025/01/0005',
			'issue_date'      => '2025-01-15',
			'sale_date'       => '2025-01-14',
			'due_date'        => '2025-01-29',
			'buyer_data'      => json_encode( array( 'name' => 'Buyer Co', 'nip' => '1111111111' ) ),
			'seller_data'     => json_encode( array( 'name' => 'Seller Co', 'details' => 'NIP: 2222222222' ) ),
			'subtotal'        => 500.00,
			'tax_total'       => 115.00,
			'total'           => 615.00,
			'currency'        => 'PLN',
			'status'          => 'issued',
			'notes'           => 'Some notes',
			'payment_method'  => 'transfer',
		);

		$invoice = Invoice::fromArray( $data );

		$this->assertEquals( 5, $invoice->getId() );
		$this->assertEquals( 100, $invoice->getOrderId() );
		$this->assertEquals( 'FV/2025/01/0005', $invoice->getDocumentNumber() );
		$this->assertEquals( '2025-01-15', $invoice->getIssueDate()->format( 'Y-m-d' ) );
		$this->assertEquals( '2025-01-14', $invoice->getSaleDate()->format( 'Y-m-d' ) );
		$this->assertEquals( '2025-01-29', $invoice->getDueDate()->format( 'Y-m-d' ) );
		$this->assertEquals( 'Buyer Co', $invoice->getBuyer()->getName() );
		$this->assertEquals( 'Seller Co', $invoice->getSeller()->getName() );
		$this->assertEquals( 500.00, $invoice->getSubtotal() );
		$this->assertEquals( 115.00, $invoice->getTaxTotal() );
		$this->assertEquals( 615.00, $invoice->getTotal() );
		$this->assertEquals( 'issued', $invoice->getStatus() );
		$this->assertEquals( 'Some notes', $invoice->getNotes() );
		$this->assertEquals( 'transfer', $invoice->getPaymentMethod() );
	}

	/**
	 * Test fromArray with array buyer/seller data.
	 */
	public function test_from_array_with_array_buyer_seller(): void {
		$data = array(
			'buyer_data'  => array( 'name' => 'Direct Buyer', 'nip' => '3333333333' ),
			'seller_data' => array( 'name' => 'Direct Seller', 'details' => 'NIP: 4444444444' ),
		);

		$invoice = Invoice::fromArray( $data );

		$this->assertEquals( 'Direct Buyer', $invoice->getBuyer()->getName() );
		$this->assertEquals( 'Direct Seller', $invoice->getSeller()->getName() );
	}

	/**
	 * Test toArray method.
	 */
	public function test_to_array(): void {
		$invoice = new Invoice();
		$invoice->setId( 1 )
			->setOrderId( 50 )
			->setDocumentNumber( 'FV/2025/01/0001' )
			->setIssueDate( new \DateTimeImmutable( '2025-01-10' ) )
			->setSaleDate( new \DateTimeImmutable( '2025-01-09' ) )
			->setDueDate( new \DateTimeImmutable( '2025-01-24' ) )
			->setBuyer( new Buyer( name: 'Test Buyer', nip: '1234567890' ) )
			->setSeller( new Seller( name: 'Test Seller', details: 'NIP: 0987654321' ) )
			->setSubtotal( 100.00 )
			->setTaxTotal( 23.00 )
			->setTotal( 123.00 )
			->setCurrency( 'PLN' )
			->setStatus( Document::STATUS_DRAFT )
			->setNotes( 'Notes' )
			->setPaymentMethod( 'cash' );

		$array = $invoice->toArray();

		$this->assertEquals( 1, $array['id'] );
		$this->assertEquals( 50, $array['order_id'] );
		$this->assertEquals( 'invoice', $array['document_type'] );
		$this->assertEquals( 'FV/2025/01/0001', $array['document_number'] );
		$this->assertEquals( '2025-01-10', $array['issue_date'] );
		$this->assertEquals( '2025-01-09', $array['sale_date'] );
		$this->assertEquals( '2025-01-24', $array['due_date'] );
		$this->assertJson( $array['buyer_data'] );
		$this->assertJson( $array['seller_data'] );
		$this->assertEquals( 100.00, $array['subtotal'] );
		$this->assertEquals( 23.00, $array['tax_total'] );
		$this->assertEquals( 123.00, $array['total'] );
		$this->assertEquals( 'draft', $array['status'] );
		$this->assertEquals( 'Notes', $array['notes'] );
		$this->assertEquals( 'cash', $array['payment_method'] );
	}

	/**
	 * Test default payment term constant.
	 */
	public function test_default_payment_term(): void {
		$this->assertEquals( 14, Invoice::DEFAULT_PAYMENT_TERM );
	}

	/**
	 * Test getStatuses returns all valid statuses.
	 */
	public function test_get_statuses(): void {
		$statuses = Document::getStatuses();

		$this->assertArrayHasKey( Document::STATUS_DRAFT, $statuses );
		$this->assertArrayHasKey( Document::STATUS_ISSUED, $statuses );
		$this->assertArrayHasKey( Document::STATUS_SENT, $statuses );
		$this->assertArrayHasKey( Document::STATUS_PAID, $statuses );
		$this->assertArrayHasKey( Document::STATUS_CANCELLED, $statuses );
	}

	/**
	 * Test getStatusLabel.
	 */
	public function test_get_status_label(): void {
		$invoice = new Invoice();

		$invoice->setStatus( Document::STATUS_DRAFT );
		$this->assertEquals( 'Draft', $invoice->getStatusLabel() );

		$invoice->setStatus( Document::STATUS_ISSUED );
		$this->assertEquals( 'Issued', $invoice->getStatusLabel() );
	}

	/**
	 * Test canBeEdited.
	 */
	public function test_can_be_edited(): void {
		$invoice = new Invoice();

		// Draft can be edited
		$this->assertTrue( $invoice->canBeEdited() );

		// Issued cannot be edited
		$invoice->setStatus( Document::STATUS_ISSUED );
		$this->assertFalse( $invoice->canBeEdited() );

		// Paid cannot be edited
		$invoice->setStatus( Document::STATUS_PAID );
		$this->assertFalse( $invoice->canBeEdited() );
	}

	/**
	 * Test invalid payment method throws exception.
	 */
	public function test_invalid_payment_method_throws_exception(): void {
		$invoice = new Invoice();

		$this->expectException( \InvalidArgumentException::class );
		$invoice->setPaymentMethod( 'invalid_method' );
	}

	/**
	 * Test empty payment method is allowed.
	 */
	public function test_empty_payment_method_is_allowed(): void {
		$invoice = new Invoice();

		$invoice->setPaymentMethod( '' );
		$this->assertEquals( '', $invoice->getPaymentMethod() );
	}

	/**
	 * Test fromArray with invalid date returns null.
	 */
	public function test_from_array_with_invalid_date(): void {
		$data = array(
			'issue_date' => 'invalid-date-format',
			'sale_date'  => 'not-a-date',
		);

		$invoice = Invoice::fromArray( $data );

		$this->assertNull( $invoice->getIssueDate() );
		$this->assertNull( $invoice->getSaleDate() );
	}

	/**
	 * Test fromArray with items.
	 */
	public function test_from_array_with_items(): void {
		$data = array(
			'id'    => 1,
			'items' => array(
				array(
					'name'           => 'Product 1',
					'quantity'       => 2.0,
					'unit_price_net' => 100.00,
					'tax_rate'       => 23.0,
				),
				array(
					'name'           => 'Product 2',
					'quantity'       => 1.0,
					'unit_price_net' => 50.00,
					'tax_rate'       => 23.0,
				),
			),
		);

		$invoice = Invoice::fromArray( $data );

		$this->assertCount( 2, $invoice->getItems() );
		$this->assertEquals( 'Product 1', $invoice->getItems()[0]->getName() );
		$this->assertEquals( 2.0, $invoice->getItems()[0]->getQuantity() );
		$this->assertEquals( 'Product 2', $invoice->getItems()[1]->getName() );
	}

	/**
	 * Test toArray includes items.
	 */
	public function test_to_array_includes_items(): void {
		$invoice = new Invoice();
		$invoice->setId( 1 );

		$item1 = new DocumentItem();
		$item1->setName( 'Item 1' )->setQuantity( 2.0 );

		$item2 = new DocumentItem();
		$item2->setName( 'Item 2' )->setQuantity( 3.0 );

		$invoice->addItem( $item1 );
		$invoice->addItem( $item2 );

		$array = $invoice->toArray();

		$this->assertArrayHasKey( 'items', $array );
		$this->assertCount( 2, $array['items'] );
		$this->assertEquals( 'Item 1', $array['items'][0]['name'] );
		$this->assertEquals( 2.0, $array['items'][0]['quantity'] );
		$this->assertEquals( 'Item 2', $array['items'][1]['name'] );
	}

	/**
	 * Test toArray with null values.
	 */
	public function test_to_array_with_null_values(): void {
		$invoice = new Invoice();

		$array = $invoice->toArray();

		$this->assertNull( $array['id'] );
		$this->assertNull( $array['order_id'] );
		$this->assertNull( $array['issue_date'] );
		$this->assertNull( $array['sale_date'] );
		$this->assertNull( $array['due_date'] );
		$this->assertNull( $array['buyer_data'] );
		$this->assertNull( $array['seller_data'] );
		$this->assertNull( $array['corrected_document_id'] );
		$this->assertEmpty( $array['items'] );
	}

	/**
	 * Test corrected document ID.
	 */
	public function test_corrected_document_id(): void {
		$invoice = new Invoice();

		$this->assertNull( $invoice->getCorrectedDocumentId() );
		$this->assertFalse( $invoice->isCorrection() );

		$invoice->setCorrectedDocumentId( 5 );

		$this->assertEquals( 5, $invoice->getCorrectedDocumentId() );
		$this->assertTrue( $invoice->isCorrection() );
	}

	/**
	 * Test fromArray with corrected_document_id.
	 */
	public function test_from_array_with_corrected_document_id(): void {
		$data = array(
			'id'                    => 10,
			'corrected_document_id' => 5,
		);

		$invoice = Invoice::fromArray( $data );

		$this->assertEquals( 10, $invoice->getId() );
		$this->assertEquals( 5, $invoice->getCorrectedDocumentId() );
		$this->assertTrue( $invoice->isCorrection() );
	}

	/**
	 * Test toArray includes corrected_document_id.
	 */
	public function test_to_array_includes_corrected_document_id(): void {
		$invoice = new Invoice();
		$invoice->setId( 10 );
		$invoice->setCorrectedDocumentId( 5 );

		$array = $invoice->toArray();

		$this->assertArrayHasKey( 'corrected_document_id', $array );
		$this->assertEquals( 5, $array['corrected_document_id'] );
	}
}
