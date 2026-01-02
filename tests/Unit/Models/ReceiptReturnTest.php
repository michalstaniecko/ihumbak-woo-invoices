<?php
/**
 * ReceiptReturn unit tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Models
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Models;

use IHumbak\Invoices\Models\ReceiptReturn;
use IHumbak\Invoices\Models\Document;
use IHumbak\Invoices\Models\Buyer;
use IHumbak\Invoices\Models\Seller;
use IHumbak\Invoices\Models\DocumentItem;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ReceiptReturn model.
 */
class ReceiptReturnTest extends TestCase {

	/**
	 * Test receipt return extends Document.
	 */
	public function test_extends_document(): void {
		$receipt_return = new ReceiptReturn();
		$this->assertInstanceOf( Document::class, $receipt_return );
	}

	/**
	 * Test document type.
	 */
	public function test_document_type(): void {
		$receipt_return = new ReceiptReturn();
		$this->assertEquals( 'receipt_return', $receipt_return->getDocumentType() );
		$this->assertEquals( ReceiptReturn::TYPE, $receipt_return->getDocumentType() );
	}

	/**
	 * Test document type label.
	 */
	public function test_document_type_label(): void {
		$receipt_return = new ReceiptReturn();
		$this->assertEquals( 'Receipt Return', $receipt_return->getDocumentTypeLabel() );
	}

	/**
	 * Test default status.
	 */
	public function test_default_status(): void {
		$receipt_return = new ReceiptReturn();
		$this->assertEquals( Document::STATUS_DRAFT, $receipt_return->getStatus() );
		$this->assertTrue( $receipt_return->isDraft() );
	}

	/**
	 * Test correction reason.
	 */
	public function test_correction_reason(): void {
		$receipt_return = new ReceiptReturn();

		$this->assertEquals( '', $receipt_return->getCorrectionReason() );

		$receipt_return->setCorrectionReason( 'Customer returned items - defective product' );
		$this->assertEquals( 'Customer returned items - defective product', $receipt_return->getCorrectionReason() );
	}

	/**
	 * Test correction type default.
	 */
	public function test_correction_type_default(): void {
		$receipt_return = new ReceiptReturn();

		$this->assertEquals( ReceiptReturn::CORRECTION_TYPE_PARTIAL, $receipt_return->getCorrectionType() );
		$this->assertTrue( $receipt_return->isPartialCorrection() );
		$this->assertFalse( $receipt_return->isFullCorrection() );
	}

	/**
	 * Test set correction type to full.
	 */
	public function test_set_correction_type_full(): void {
		$receipt_return = new ReceiptReturn();

		$receipt_return->setCorrectionType( ReceiptReturn::CORRECTION_TYPE_FULL );

		$this->assertEquals( ReceiptReturn::CORRECTION_TYPE_FULL, $receipt_return->getCorrectionType() );
		$this->assertTrue( $receipt_return->isFullCorrection() );
		$this->assertFalse( $receipt_return->isPartialCorrection() );
	}

	/**
	 * Test invalid correction type throws exception.
	 */
	public function test_invalid_correction_type_throws_exception(): void {
		$receipt_return = new ReceiptReturn();

		$this->expectException( \InvalidArgumentException::class );
		$receipt_return->setCorrectionType( 'invalid_type' );
	}

	/**
	 * Test refund ID.
	 */
	public function test_refund_id(): void {
		$receipt_return = new ReceiptReturn();

		$this->assertNull( $receipt_return->getRefundId() );
		$this->assertFalse( $receipt_return->hasRefund() );

		$receipt_return->setRefundId( 123 );

		$this->assertEquals( 123, $receipt_return->getRefundId() );
		$this->assertTrue( $receipt_return->hasRefund() );
	}

	/**
	 * Test set refund ID to null.
	 */
	public function test_set_refund_id_to_null(): void {
		$receipt_return = new ReceiptReturn();
		$receipt_return->setRefundId( 100 );
		$receipt_return->setRefundId( null );

		$this->assertNull( $receipt_return->getRefundId() );
		$this->assertFalse( $receipt_return->hasRefund() );
	}

	/**
	 * Test get correction types.
	 */
	public function test_get_correction_types(): void {
		$types = ReceiptReturn::getCorrectionTypes();

		$this->assertArrayHasKey( ReceiptReturn::CORRECTION_TYPE_PARTIAL, $types );
		$this->assertArrayHasKey( ReceiptReturn::CORRECTION_TYPE_FULL, $types );
		$this->assertEquals( 'Partial Return', $types[ ReceiptReturn::CORRECTION_TYPE_PARTIAL ] );
		$this->assertEquals( 'Full Return', $types[ ReceiptReturn::CORRECTION_TYPE_FULL ] );
	}

	/**
	 * Test is correction returns true when corrected_document_id is set.
	 */
	public function test_is_correction_returns_true(): void {
		$receipt_return = new ReceiptReturn();

		$this->assertFalse( $receipt_return->isCorrection() );

		$receipt_return->setCorrectedDocumentId( 5 );

		$this->assertTrue( $receipt_return->isCorrection() );
	}

	/**
	 * Test fluent setters.
	 */
	public function test_fluent_setters(): void {
		$receipt_return = new ReceiptReturn();
		$date           = new \DateTimeImmutable( '2025-01-15' );
		$buyer          = new Buyer( name: 'Test Buyer' );
		$seller         = new Seller( name: 'Test Seller', details: 'NIP: 1234567890' );

		$result = $receipt_return
			->setId( 1 )
			->setOrderId( 100 )
			->setDocumentNumber( 'RR/2025/01/0001' )
			->setIssueDate( $date )
			->setSaleDate( $date )
			->setCorrectedDocumentId( 50 )
			->setCorrectionReason( 'Customer return' )
			->setCorrectionType( ReceiptReturn::CORRECTION_TYPE_PARTIAL )
			->setRefundId( 200 )
			->setBuyer( $buyer )
			->setSeller( $seller )
			->setSubtotal( -100.00 )
			->setTaxTotal( -23.00 )
			->setTotal( -123.00 )
			->setCurrency( 'PLN' )
			->setNotes( 'Test notes' );

		$this->assertSame( $receipt_return, $result );
		$this->assertEquals( 1, $receipt_return->getId() );
		$this->assertEquals( 100, $receipt_return->getOrderId() );
		$this->assertEquals( 'RR/2025/01/0001', $receipt_return->getDocumentNumber() );
		$this->assertEquals( $date, $receipt_return->getIssueDate() );
		$this->assertEquals( 50, $receipt_return->getCorrectedDocumentId() );
		$this->assertEquals( 'Customer return', $receipt_return->getCorrectionReason() );
		$this->assertEquals( ReceiptReturn::CORRECTION_TYPE_PARTIAL, $receipt_return->getCorrectionType() );
		$this->assertEquals( 200, $receipt_return->getRefundId() );
		$this->assertEquals( $buyer, $receipt_return->getBuyer() );
		$this->assertEquals( $seller, $receipt_return->getSeller() );
		$this->assertEquals( -100.00, $receipt_return->getSubtotal() );
		$this->assertEquals( -23.00, $receipt_return->getTaxTotal() );
		$this->assertEquals( -123.00, $receipt_return->getTotal() );
	}

	/**
	 * Test negative totals are allowed.
	 */
	public function test_negative_totals_allowed(): void {
		$receipt_return = new ReceiptReturn();

		$receipt_return->setSubtotal( -500.00 );
		$receipt_return->setTaxTotal( -115.00 );
		$receipt_return->setTotal( -615.00 );

		$this->assertEquals( -500.00, $receipt_return->getSubtotal() );
		$this->assertEquals( -115.00, $receipt_return->getTaxTotal() );
		$this->assertEquals( -615.00, $receipt_return->getTotal() );
	}

	/**
	 * Test fromArray factory method.
	 */
	public function test_from_array(): void {
		$data = array(
			'id'                    => 10,
			'order_id'              => 100,
			'document_number'       => 'RR/2025/01/0001',
			'issue_date'            => '2025-01-15',
			'sale_date'             => '2025-01-14',
			'corrected_document_id' => 5,
			'correction_reason'     => 'Product return',
			'correction_type'       => 'partial',
			'refund_id'             => 42,
			'buyer_data'            => json_encode( array( 'name' => 'Buyer Co' ) ),
			'seller_data'           => json_encode( array( 'name' => 'Seller Co', 'details' => 'NIP: 2222222222' ) ),
			'subtotal'              => -100.00,
			'tax_total'             => -23.00,
			'total'                 => -123.00,
			'currency'              => 'PLN',
			'status'                => 'issued',
			'notes'                 => 'Some notes',
		);

		$receipt_return = ReceiptReturn::fromArray( $data );

		$this->assertEquals( 10, $receipt_return->getId() );
		$this->assertEquals( 100, $receipt_return->getOrderId() );
		$this->assertEquals( 'RR/2025/01/0001', $receipt_return->getDocumentNumber() );
		$this->assertEquals( '2025-01-15', $receipt_return->getIssueDate()->format( 'Y-m-d' ) );
		$this->assertEquals( '2025-01-14', $receipt_return->getSaleDate()->format( 'Y-m-d' ) );
		$this->assertEquals( 5, $receipt_return->getCorrectedDocumentId() );
		$this->assertEquals( 'Product return', $receipt_return->getCorrectionReason() );
		$this->assertEquals( 'partial', $receipt_return->getCorrectionType() );
		$this->assertEquals( 42, $receipt_return->getRefundId() );
		$this->assertEquals( 'Buyer Co', $receipt_return->getBuyer()->getName() );
		$this->assertEquals( 'Seller Co', $receipt_return->getSeller()->getName() );
		$this->assertEquals( -100.00, $receipt_return->getSubtotal() );
		$this->assertEquals( -23.00, $receipt_return->getTaxTotal() );
		$this->assertEquals( -123.00, $receipt_return->getTotal() );
		$this->assertEquals( 'issued', $receipt_return->getStatus() );
		$this->assertEquals( 'Some notes', $receipt_return->getNotes() );
		$this->assertTrue( $receipt_return->isCorrection() );
		$this->assertTrue( $receipt_return->hasRefund() );
	}

	/**
	 * Test fromArray with invalid correction type uses default.
	 */
	public function test_from_array_with_invalid_correction_type_uses_default(): void {
		$data = array(
			'id'              => 1,
			'correction_type' => 'invalid_type',
		);

		$receipt_return = ReceiptReturn::fromArray( $data );

		$this->assertEquals( ReceiptReturn::CORRECTION_TYPE_PARTIAL, $receipt_return->getCorrectionType() );
	}

	/**
	 * Test toArray method.
	 */
	public function test_to_array(): void {
		$receipt_return = new ReceiptReturn();
		$receipt_return->setId( 10 )
			->setOrderId( 50 )
			->setDocumentNumber( 'RR/2025/01/0001' )
			->setIssueDate( new \DateTimeImmutable( '2025-01-10' ) )
			->setSaleDate( new \DateTimeImmutable( '2025-01-09' ) )
			->setCorrectedDocumentId( 5 )
			->setCorrectionReason( 'Test reason' )
			->setCorrectionType( ReceiptReturn::CORRECTION_TYPE_FULL )
			->setRefundId( 99 )
			->setBuyer( new Buyer( name: 'Test Buyer' ) )
			->setSeller( new Seller( name: 'Test Seller', details: 'NIP: 0987654321' ) )
			->setSubtotal( -100.00 )
			->setTaxTotal( -23.00 )
			->setTotal( -123.00 )
			->setCurrency( 'PLN' )
			->setStatus( Document::STATUS_ISSUED )
			->setNotes( 'Notes' );

		$array = $receipt_return->toArray();

		$this->assertEquals( 10, $array['id'] );
		$this->assertEquals( 50, $array['order_id'] );
		$this->assertEquals( 'receipt_return', $array['document_type'] );
		$this->assertEquals( 'RR/2025/01/0001', $array['document_number'] );
		$this->assertEquals( '2025-01-10', $array['issue_date'] );
		$this->assertEquals( '2025-01-09', $array['sale_date'] );
		$this->assertNull( $array['due_date'] ); // Receipt returns don't have due date.
		$this->assertEquals( 5, $array['corrected_document_id'] );
		$this->assertEquals( 'Test reason', $array['correction_reason'] );
		$this->assertEquals( 'full', $array['correction_type'] );
		$this->assertEquals( 99, $array['refund_id'] );
		$this->assertJson( $array['buyer_data'] );
		$this->assertJson( $array['seller_data'] );
		$this->assertEquals( -100.00, $array['subtotal'] );
		$this->assertEquals( -23.00, $array['tax_total'] );
		$this->assertEquals( -123.00, $array['total'] );
		$this->assertEquals( 'issued', $array['status'] );
		$this->assertEquals( 'Notes', $array['notes'] );
	}

	/**
	 * Test constants are defined correctly.
	 */
	public function test_constants(): void {
		$this->assertEquals( 'receipt_return', ReceiptReturn::TYPE );
		$this->assertEquals( 'full', ReceiptReturn::CORRECTION_TYPE_FULL );
		$this->assertEquals( 'partial', ReceiptReturn::CORRECTION_TYPE_PARTIAL );
	}

	/**
	 * Test manual entry mode default is false.
	 */
	public function test_manual_entry_default(): void {
		$receipt_return = new ReceiptReturn();

		$this->assertFalse( $receipt_return->isManualEntry() );
	}

	/**
	 * Test set manual entry mode.
	 */
	public function test_set_manual_entry(): void {
		$receipt_return = new ReceiptReturn();

		$result = $receipt_return->setManualEntry( true );

		$this->assertSame( $receipt_return, $result );
		$this->assertTrue( $receipt_return->isManualEntry() );

		$receipt_return->setManualEntry( false );
		$this->assertFalse( $receipt_return->isManualEntry() );
	}

	/**
	 * Test original document number.
	 */
	public function test_original_document_number(): void {
		$receipt_return = new ReceiptReturn();

		$this->assertNull( $receipt_return->getOriginalDocumentNumber() );

		$result = $receipt_return->setOriginalDocumentNumber( 'PAR/2024/12/0001' );

		$this->assertSame( $receipt_return, $result );
		$this->assertEquals( 'PAR/2024/12/0001', $receipt_return->getOriginalDocumentNumber() );

		$receipt_return->setOriginalDocumentNumber( null );
		$this->assertNull( $receipt_return->getOriginalDocumentNumber() );
	}

	/**
	 * Test original document date.
	 */
	public function test_original_document_date(): void {
		$receipt_return = new ReceiptReturn();

		$this->assertNull( $receipt_return->getOriginalDocumentDate() );

		$date   = new \DateTimeImmutable( '2024-12-15' );
		$result = $receipt_return->setOriginalDocumentDate( $date );

		$this->assertSame( $receipt_return, $result );
		$this->assertEquals( $date, $receipt_return->getOriginalDocumentDate() );
		$this->assertEquals( '2024-12-15', $receipt_return->getOriginalDocumentDate()->format( 'Y-m-d' ) );

		$receipt_return->setOriginalDocumentDate( null );
		$this->assertNull( $receipt_return->getOriginalDocumentDate() );
	}

	/**
	 * Test getDisplayCorrectedDocumentNumber for manual entry mode.
	 */
	public function test_display_corrected_document_number_manual_mode(): void {
		$receipt_return = new ReceiptReturn();
		$receipt_return->setManualEntry( true );
		$receipt_return->setOriginalDocumentNumber( 'PAR/2024/OLD/0099' );

		$this->assertEquals( 'PAR/2024/OLD/0099', $receipt_return->getDisplayCorrectedDocumentNumber() );
	}

	/**
	 * Test getDisplayCorrectedDocumentNumber for system mode returns null.
	 */
	public function test_display_corrected_document_number_system_mode(): void {
		$receipt_return = new ReceiptReturn();
		$receipt_return->setManualEntry( false );
		$receipt_return->setCorrectedDocumentId( 5 );

		$this->assertNull( $receipt_return->getDisplayCorrectedDocumentNumber() );
	}

	/**
	 * Test fromArray with manual entry fields.
	 */
	public function test_from_array_with_manual_entry(): void {
		$data = array(
			'id'                       => 15,
			'is_manual_entry'          => true,
			'original_document_number' => 'OLD/PAR/2024/0001',
			'original_document_date'   => '2024-06-15',
			'correction_reason'        => 'Return for external receipt',
		);

		$receipt_return = ReceiptReturn::fromArray( $data );

		$this->assertEquals( 15, $receipt_return->getId() );
		$this->assertTrue( $receipt_return->isManualEntry() );
		$this->assertEquals( 'OLD/PAR/2024/0001', $receipt_return->getOriginalDocumentNumber() );
		$this->assertEquals( '2024-06-15', $receipt_return->getOriginalDocumentDate()->format( 'Y-m-d' ) );
		$this->assertEquals( 'Return for external receipt', $receipt_return->getCorrectionReason() );
	}

	/**
	 * Test toArray includes manual entry fields.
	 */
	public function test_to_array_includes_manual_entry_fields(): void {
		$receipt_return = new ReceiptReturn();
		$receipt_return->setId( 20 )
			->setManualEntry( true )
			->setOriginalDocumentNumber( 'EXT/2023/0050' )
			->setOriginalDocumentDate( new \DateTimeImmutable( '2023-11-30' ) )
			->setCorrectionReason( 'Manual return' );

		$array = $receipt_return->toArray();

		$this->assertTrue( $array['is_manual_entry'] );
		$this->assertEquals( 'EXT/2023/0050', $array['original_document_number'] );
		$this->assertEquals( '2023-11-30', $array['original_document_date'] );
		$this->assertNull( $array['corrected_document_id'] );
	}

	/**
	 * Test status transitions.
	 */
	public function test_status_transitions(): void {
		$receipt_return = new ReceiptReturn();

		$receipt_return->setStatus( Document::STATUS_ISSUED );
		$this->assertTrue( $receipt_return->isIssued() );
		$this->assertFalse( $receipt_return->isDraft() );
		$this->assertFalse( $receipt_return->canBeEdited() );

		$receipt_return->setStatus( Document::STATUS_CANCELLED );
		$this->assertTrue( $receipt_return->isCancelled() );
	}

	/**
	 * Test items management.
	 */
	public function test_items_management(): void {
		$receipt_return = new ReceiptReturn();

		$item1 = new DocumentItem();
		$item1->setName( 'Item 1' )->setQuantity( -1.0 );

		$item2 = new DocumentItem();
		$item2->setName( 'Item 2' )->setQuantity( -2.0 );

		$receipt_return->addItem( $item1 );
		$receipt_return->addItem( $item2 );

		$items = $receipt_return->getItems();
		$this->assertCount( 2, $items );
		$this->assertSame( $item1, $items[0] );
		$this->assertSame( $item2, $items[1] );

		// Test setItems.
		$receipt_return->setItems( array( $item1 ) );
		$this->assertCount( 1, $receipt_return->getItems() );
	}
}
