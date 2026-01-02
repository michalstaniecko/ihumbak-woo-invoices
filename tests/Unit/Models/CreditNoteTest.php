<?php
/**
 * CreditNote unit tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Models
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Models;

use IHumbak\Invoices\Models\CreditNote;
use IHumbak\Invoices\Models\Document;
use IHumbak\Invoices\Models\Buyer;
use IHumbak\Invoices\Models\Seller;
use IHumbak\Invoices\Models\DocumentItem;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CreditNote model.
 */
class CreditNoteTest extends TestCase {

	/**
	 * Test credit note extends Document.
	 */
	public function test_extends_document(): void {
		$credit_note = new CreditNote();
		$this->assertInstanceOf( Document::class, $credit_note );
	}

	/**
	 * Test document type.
	 */
	public function test_document_type(): void {
		$credit_note = new CreditNote();
		$this->assertEquals( 'credit_note', $credit_note->getDocumentType() );
		$this->assertEquals( CreditNote::TYPE, $credit_note->getDocumentType() );
	}

	/**
	 * Test document type label.
	 */
	public function test_document_type_label(): void {
		$credit_note = new CreditNote();
		$this->assertEquals( 'Credit Note', $credit_note->getDocumentTypeLabel() );
	}

	/**
	 * Test default status.
	 */
	public function test_default_status(): void {
		$credit_note = new CreditNote();
		$this->assertEquals( Document::STATUS_DRAFT, $credit_note->getStatus() );
		$this->assertTrue( $credit_note->isDraft() );
	}

	/**
	 * Test correction reason.
	 */
	public function test_correction_reason(): void {
		$credit_note = new CreditNote();

		$this->assertEquals( '', $credit_note->getCorrectionReason() );

		$credit_note->setCorrectionReason( 'Quantity error - customer received 2 instead of 3 items' );
		$this->assertEquals( 'Quantity error - customer received 2 instead of 3 items', $credit_note->getCorrectionReason() );
	}

	/**
	 * Test correction type default.
	 */
	public function test_correction_type_default(): void {
		$credit_note = new CreditNote();

		$this->assertEquals( CreditNote::CORRECTION_TYPE_PARTIAL, $credit_note->getCorrectionType() );
		$this->assertTrue( $credit_note->isPartialCorrection() );
		$this->assertFalse( $credit_note->isFullCorrection() );
	}

	/**
	 * Test set correction type to full.
	 */
	public function test_set_correction_type_full(): void {
		$credit_note = new CreditNote();

		$credit_note->setCorrectionType( CreditNote::CORRECTION_TYPE_FULL );

		$this->assertEquals( CreditNote::CORRECTION_TYPE_FULL, $credit_note->getCorrectionType() );
		$this->assertTrue( $credit_note->isFullCorrection() );
		$this->assertFalse( $credit_note->isPartialCorrection() );
	}

	/**
	 * Test invalid correction type throws exception.
	 */
	public function test_invalid_correction_type_throws_exception(): void {
		$credit_note = new CreditNote();

		$this->expectException( \InvalidArgumentException::class );
		$credit_note->setCorrectionType( 'invalid_type' );
	}

	/**
	 * Test refund ID.
	 */
	public function test_refund_id(): void {
		$credit_note = new CreditNote();

		$this->assertNull( $credit_note->getRefundId() );
		$this->assertFalse( $credit_note->hasRefund() );

		$credit_note->setRefundId( 123 );

		$this->assertEquals( 123, $credit_note->getRefundId() );
		$this->assertTrue( $credit_note->hasRefund() );
	}

	/**
	 * Test set refund ID to null.
	 */
	public function test_set_refund_id_to_null(): void {
		$credit_note = new CreditNote();
		$credit_note->setRefundId( 100 );
		$credit_note->setRefundId( null );

		$this->assertNull( $credit_note->getRefundId() );
		$this->assertFalse( $credit_note->hasRefund() );
	}

	/**
	 * Test get correction types.
	 */
	public function test_get_correction_types(): void {
		$types = CreditNote::getCorrectionTypes();

		$this->assertArrayHasKey( CreditNote::CORRECTION_TYPE_PARTIAL, $types );
		$this->assertArrayHasKey( CreditNote::CORRECTION_TYPE_FULL, $types );
		$this->assertEquals( 'Partial Correction', $types[ CreditNote::CORRECTION_TYPE_PARTIAL ] );
		$this->assertEquals( 'Full Correction', $types[ CreditNote::CORRECTION_TYPE_FULL ] );
	}

	/**
	 * Test is correction returns true when corrected_document_id is set.
	 */
	public function test_is_correction_returns_true(): void {
		$credit_note = new CreditNote();

		$this->assertFalse( $credit_note->isCorrection() );

		$credit_note->setCorrectedDocumentId( 5 );

		$this->assertTrue( $credit_note->isCorrection() );
	}

	/**
	 * Test fluent setters.
	 */
	public function test_fluent_setters(): void {
		$credit_note = new CreditNote();
		$date        = new \DateTimeImmutable( '2025-01-15' );
		$buyer       = new Buyer( name: 'Test Buyer' );
		$seller      = new Seller( name: 'Test Seller', details: 'NIP: 1234567890' );

		$result = $credit_note
			->setId( 1 )
			->setOrderId( 100 )
			->setDocumentNumber( 'CN/2025/01/0001' )
			->setIssueDate( $date )
			->setSaleDate( $date )
			->setCorrectedDocumentId( 50 )
			->setCorrectionReason( 'Price adjustment' )
			->setCorrectionType( CreditNote::CORRECTION_TYPE_PARTIAL )
			->setRefundId( 200 )
			->setBuyer( $buyer )
			->setSeller( $seller )
			->setSubtotal( -100.00 )
			->setTaxTotal( -23.00 )
			->setTotal( -123.00 )
			->setCurrency( 'PLN' )
			->setNotes( 'Test notes' );

		$this->assertSame( $credit_note, $result );
		$this->assertEquals( 1, $credit_note->getId() );
		$this->assertEquals( 100, $credit_note->getOrderId() );
		$this->assertEquals( 'CN/2025/01/0001', $credit_note->getDocumentNumber() );
		$this->assertEquals( $date, $credit_note->getIssueDate() );
		$this->assertEquals( 50, $credit_note->getCorrectedDocumentId() );
		$this->assertEquals( 'Price adjustment', $credit_note->getCorrectionReason() );
		$this->assertEquals( CreditNote::CORRECTION_TYPE_PARTIAL, $credit_note->getCorrectionType() );
		$this->assertEquals( 200, $credit_note->getRefundId() );
		$this->assertEquals( $buyer, $credit_note->getBuyer() );
		$this->assertEquals( $seller, $credit_note->getSeller() );
		$this->assertEquals( -100.00, $credit_note->getSubtotal() );
		$this->assertEquals( -23.00, $credit_note->getTaxTotal() );
		$this->assertEquals( -123.00, $credit_note->getTotal() );
	}

	/**
	 * Test negative totals are allowed.
	 */
	public function test_negative_totals_allowed(): void {
		$credit_note = new CreditNote();

		$credit_note->setSubtotal( -500.00 );
		$credit_note->setTaxTotal( -115.00 );
		$credit_note->setTotal( -615.00 );

		$this->assertEquals( -500.00, $credit_note->getSubtotal() );
		$this->assertEquals( -115.00, $credit_note->getTaxTotal() );
		$this->assertEquals( -615.00, $credit_note->getTotal() );
	}

	/**
	 * Test fromArray factory method.
	 */
	public function test_from_array(): void {
		$data = array(
			'id'                    => 10,
			'order_id'              => 100,
			'document_number'       => 'CN/2025/01/0001',
			'issue_date'            => '2025-01-15',
			'sale_date'             => '2025-01-14',
			'corrected_document_id' => 5,
			'correction_reason'     => 'Quantity error',
			'correction_type'       => 'partial',
			'refund_id'             => 42,
			'buyer_data'            => json_encode( array( 'name' => 'Buyer Co', 'nip' => '1111111111' ) ),
			'seller_data'           => json_encode( array( 'name' => 'Seller Co', 'details' => 'NIP: 2222222222' ) ),
			'subtotal'              => -100.00,
			'tax_total'             => -23.00,
			'total'                 => -123.00,
			'currency'              => 'PLN',
			'status'                => 'issued',
			'notes'                 => 'Some notes',
		);

		$credit_note = CreditNote::fromArray( $data );

		$this->assertEquals( 10, $credit_note->getId() );
		$this->assertEquals( 100, $credit_note->getOrderId() );
		$this->assertEquals( 'CN/2025/01/0001', $credit_note->getDocumentNumber() );
		$this->assertEquals( '2025-01-15', $credit_note->getIssueDate()->format( 'Y-m-d' ) );
		$this->assertEquals( '2025-01-14', $credit_note->getSaleDate()->format( 'Y-m-d' ) );
		$this->assertEquals( 5, $credit_note->getCorrectedDocumentId() );
		$this->assertEquals( 'Quantity error', $credit_note->getCorrectionReason() );
		$this->assertEquals( 'partial', $credit_note->getCorrectionType() );
		$this->assertEquals( 42, $credit_note->getRefundId() );
		$this->assertEquals( 'Buyer Co', $credit_note->getBuyer()->getName() );
		$this->assertEquals( 'Seller Co', $credit_note->getSeller()->getName() );
		$this->assertEquals( -100.00, $credit_note->getSubtotal() );
		$this->assertEquals( -23.00, $credit_note->getTaxTotal() );
		$this->assertEquals( -123.00, $credit_note->getTotal() );
		$this->assertEquals( 'issued', $credit_note->getStatus() );
		$this->assertEquals( 'Some notes', $credit_note->getNotes() );
		$this->assertTrue( $credit_note->isCorrection() );
		$this->assertTrue( $credit_note->hasRefund() );
	}

	/**
	 * Test fromArray with invalid correction type uses default.
	 */
	public function test_from_array_with_invalid_correction_type_uses_default(): void {
		$data = array(
			'id'              => 1,
			'correction_type' => 'invalid_type',
		);

		$credit_note = CreditNote::fromArray( $data );

		$this->assertEquals( CreditNote::CORRECTION_TYPE_PARTIAL, $credit_note->getCorrectionType() );
	}

	/**
	 * Test fromArray with array buyer/seller data.
	 */
	public function test_from_array_with_array_buyer_seller(): void {
		$data = array(
			'buyer_data'  => array( 'name' => 'Direct Buyer', 'nip' => '3333333333' ),
			'seller_data' => array( 'name' => 'Direct Seller', 'details' => 'NIP: 4444444444' ),
		);

		$credit_note = CreditNote::fromArray( $data );

		$this->assertEquals( 'Direct Buyer', $credit_note->getBuyer()->getName() );
		$this->assertEquals( 'Direct Seller', $credit_note->getSeller()->getName() );
	}

	/**
	 * Test fromArray with items.
	 */
	public function test_from_array_with_items(): void {
		$data = array(
			'id'    => 1,
			'items' => array(
				array(
					'name'             => 'Product 1',
					'quantity'         => -1.0,
					'unit_price_net'   => 100.00,
					'tax_rate'         => 23.0,
					'line_total_net'   => -100.00,
					'line_total_gross' => -123.00,
				),
			),
		);

		$credit_note = CreditNote::fromArray( $data );

		$this->assertCount( 1, $credit_note->getItems() );
		$this->assertEquals( 'Product 1', $credit_note->getItems()[0]->getName() );
		$this->assertEquals( -1.0, $credit_note->getItems()[0]->getQuantity() );
	}

	/**
	 * Test toArray method.
	 */
	public function test_to_array(): void {
		$credit_note = new CreditNote();
		$credit_note->setId( 10 )
			->setOrderId( 50 )
			->setDocumentNumber( 'CN/2025/01/0001' )
			->setIssueDate( new \DateTimeImmutable( '2025-01-10' ) )
			->setSaleDate( new \DateTimeImmutable( '2025-01-09' ) )
			->setCorrectedDocumentId( 5 )
			->setCorrectionReason( 'Test reason' )
			->setCorrectionType( CreditNote::CORRECTION_TYPE_FULL )
			->setRefundId( 99 )
			->setBuyer( new Buyer( name: 'Test Buyer', nip: '1234567890' ) )
			->setSeller( new Seller( name: 'Test Seller', details: 'NIP: 0987654321' ) )
			->setSubtotal( -100.00 )
			->setTaxTotal( -23.00 )
			->setTotal( -123.00 )
			->setCurrency( 'PLN' )
			->setStatus( Document::STATUS_ISSUED )
			->setNotes( 'Notes' );

		$array = $credit_note->toArray();

		$this->assertEquals( 10, $array['id'] );
		$this->assertEquals( 50, $array['order_id'] );
		$this->assertEquals( 'credit_note', $array['document_type'] );
		$this->assertEquals( 'CN/2025/01/0001', $array['document_number'] );
		$this->assertEquals( '2025-01-10', $array['issue_date'] );
		$this->assertEquals( '2025-01-09', $array['sale_date'] );
		$this->assertNull( $array['due_date'] ); // Credit notes don't have due date.
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
	 * Test toArray includes items.
	 */
	public function test_to_array_includes_items(): void {
		$credit_note = new CreditNote();
		$credit_note->setId( 1 );

		$item = new DocumentItem();
		$item->setName( 'Item 1' )->setQuantity( -2.0 );

		$credit_note->addItem( $item );

		$array = $credit_note->toArray();

		$this->assertArrayHasKey( 'items', $array );
		$this->assertCount( 1, $array['items'] );
		$this->assertEquals( 'Item 1', $array['items'][0]['name'] );
		$this->assertEquals( -2.0, $array['items'][0]['quantity'] );
	}

	/**
	 * Test toArray with null values.
	 */
	public function test_to_array_with_null_values(): void {
		$credit_note = new CreditNote();

		$array = $credit_note->toArray();

		$this->assertNull( $array['id'] );
		$this->assertNull( $array['order_id'] );
		$this->assertNull( $array['issue_date'] );
		$this->assertNull( $array['sale_date'] );
		$this->assertNull( $array['due_date'] );
		$this->assertNull( $array['corrected_document_id'] );
		$this->assertEquals( '', $array['correction_reason'] );
		$this->assertEquals( 'partial', $array['correction_type'] );
		$this->assertNull( $array['refund_id'] );
		$this->assertEmpty( $array['items'] );
	}

	/**
	 * Test status transitions.
	 */
	public function test_status_transitions(): void {
		$credit_note = new CreditNote();

		$credit_note->setStatus( Document::STATUS_ISSUED );
		$this->assertTrue( $credit_note->isIssued() );
		$this->assertFalse( $credit_note->isDraft() );
		$this->assertFalse( $credit_note->canBeEdited() );

		$credit_note->setStatus( Document::STATUS_CANCELLED );
		$this->assertTrue( $credit_note->isCancelled() );
	}

	/**
	 * Test items management.
	 */
	public function test_items_management(): void {
		$credit_note = new CreditNote();

		$item1 = new DocumentItem();
		$item1->setName( 'Item 1' )->setQuantity( -1.0 );

		$item2 = new DocumentItem();
		$item2->setName( 'Item 2' )->setQuantity( -2.0 );

		$credit_note->addItem( $item1 );
		$credit_note->addItem( $item2 );

		$items = $credit_note->getItems();
		$this->assertCount( 2, $items );
		$this->assertSame( $item1, $items[0] );
		$this->assertSame( $item2, $items[1] );

		// Test setItems.
		$credit_note->setItems( array( $item1 ) );
		$this->assertCount( 1, $credit_note->getItems() );
	}

	/**
	 * Test fromArray with invalid date returns null.
	 */
	public function test_from_array_with_invalid_date(): void {
		$data = array(
			'issue_date' => 'invalid-date-format',
			'sale_date'  => 'not-a-date',
		);

		$credit_note = CreditNote::fromArray( $data );

		$this->assertNull( $credit_note->getIssueDate() );
		$this->assertNull( $credit_note->getSaleDate() );
	}

	/**
	 * Test constants are defined correctly.
	 */
	public function test_constants(): void {
		$this->assertEquals( 'credit_note', CreditNote::TYPE );
		$this->assertEquals( 'full', CreditNote::CORRECTION_TYPE_FULL );
		$this->assertEquals( 'partial', CreditNote::CORRECTION_TYPE_PARTIAL );
	}

	/**
	 * Test manual entry mode default is false.
	 */
	public function test_manual_entry_default(): void {
		$credit_note = new CreditNote();

		$this->assertFalse( $credit_note->isManualEntry() );
	}

	/**
	 * Test set manual entry mode.
	 */
	public function test_set_manual_entry(): void {
		$credit_note = new CreditNote();

		$result = $credit_note->setManualEntry( true );

		$this->assertSame( $credit_note, $result );
		$this->assertTrue( $credit_note->isManualEntry() );

		$credit_note->setManualEntry( false );
		$this->assertFalse( $credit_note->isManualEntry() );
	}

	/**
	 * Test original document number.
	 */
	public function test_original_document_number(): void {
		$credit_note = new CreditNote();

		$this->assertNull( $credit_note->getOriginalDocumentNumber() );

		$result = $credit_note->setOriginalDocumentNumber( 'FV/2024/12/0001' );

		$this->assertSame( $credit_note, $result );
		$this->assertEquals( 'FV/2024/12/0001', $credit_note->getOriginalDocumentNumber() );

		$credit_note->setOriginalDocumentNumber( null );
		$this->assertNull( $credit_note->getOriginalDocumentNumber() );
	}

	/**
	 * Test original document date.
	 */
	public function test_original_document_date(): void {
		$credit_note = new CreditNote();

		$this->assertNull( $credit_note->getOriginalDocumentDate() );

		$date   = new \DateTimeImmutable( '2024-12-15' );
		$result = $credit_note->setOriginalDocumentDate( $date );

		$this->assertSame( $credit_note, $result );
		$this->assertEquals( $date, $credit_note->getOriginalDocumentDate() );
		$this->assertEquals( '2024-12-15', $credit_note->getOriginalDocumentDate()->format( 'Y-m-d' ) );

		$credit_note->setOriginalDocumentDate( null );
		$this->assertNull( $credit_note->getOriginalDocumentDate() );
	}

	/**
	 * Test getDisplayCorrectedDocumentNumber for manual entry mode.
	 */
	public function test_display_corrected_document_number_manual_mode(): void {
		$credit_note = new CreditNote();
		$credit_note->setManualEntry( true );
		$credit_note->setOriginalDocumentNumber( 'FV/2024/OLD/0099' );

		$this->assertEquals( 'FV/2024/OLD/0099', $credit_note->getDisplayCorrectedDocumentNumber() );
	}

	/**
	 * Test getDisplayCorrectedDocumentNumber for system mode returns null.
	 */
	public function test_display_corrected_document_number_system_mode(): void {
		$credit_note = new CreditNote();
		$credit_note->setManualEntry( false );
		$credit_note->setCorrectedDocumentId( 5 );

		$this->assertNull( $credit_note->getDisplayCorrectedDocumentNumber() );
	}

	/**
	 * Test fromArray with manual entry fields.
	 */
	public function test_from_array_with_manual_entry(): void {
		$data = array(
			'id'                       => 15,
			'is_manual_entry'          => true,
			'original_document_number' => 'OLD/INV/2024/0001',
			'original_document_date'   => '2024-06-15',
			'correction_reason'        => 'Correction for external invoice',
		);

		$credit_note = CreditNote::fromArray( $data );

		$this->assertEquals( 15, $credit_note->getId() );
		$this->assertTrue( $credit_note->isManualEntry() );
		$this->assertEquals( 'OLD/INV/2024/0001', $credit_note->getOriginalDocumentNumber() );
		$this->assertEquals( '2024-06-15', $credit_note->getOriginalDocumentDate()->format( 'Y-m-d' ) );
		$this->assertEquals( 'Correction for external invoice', $credit_note->getCorrectionReason() );
	}

	/**
	 * Test fromArray with manual entry false.
	 */
	public function test_from_array_with_manual_entry_false(): void {
		$data = array(
			'is_manual_entry' => false,
		);

		$credit_note = CreditNote::fromArray( $data );

		$this->assertFalse( $credit_note->isManualEntry() );
	}

	/**
	 * Test fromArray with original_document_date as DateTimeImmutable.
	 */
	public function test_from_array_with_datetime_immutable_original_date(): void {
		$date = new \DateTimeImmutable( '2024-03-20' );
		$data = array(
			'original_document_date' => $date,
		);

		$credit_note = CreditNote::fromArray( $data );

		$this->assertEquals( $date, $credit_note->getOriginalDocumentDate() );
	}

	/**
	 * Test fromArray with original_document_date as DateTime.
	 */
	public function test_from_array_with_datetime_original_date(): void {
		$date = new \DateTime( '2024-03-21' );
		$data = array(
			'original_document_date' => $date,
		);

		$credit_note = CreditNote::fromArray( $data );

		$this->assertEquals( '2024-03-21', $credit_note->getOriginalDocumentDate()->format( 'Y-m-d' ) );
	}

	/**
	 * Test toArray includes manual entry fields.
	 */
	public function test_to_array_includes_manual_entry_fields(): void {
		$credit_note = new CreditNote();
		$credit_note->setId( 20 )
			->setManualEntry( true )
			->setOriginalDocumentNumber( 'EXT/2023/0050' )
			->setOriginalDocumentDate( new \DateTimeImmutable( '2023-11-30' ) )
			->setCorrectionReason( 'Manual correction' );

		$array = $credit_note->toArray();

		$this->assertTrue( $array['is_manual_entry'] );
		$this->assertEquals( 'EXT/2023/0050', $array['original_document_number'] );
		$this->assertEquals( '2023-11-30', $array['original_document_date'] );
		$this->assertNull( $array['corrected_document_id'] );
	}

	/**
	 * Test toArray with manual entry false has default values.
	 */
	public function test_to_array_manual_entry_default_values(): void {
		$credit_note = new CreditNote();

		$array = $credit_note->toArray();

		$this->assertFalse( $array['is_manual_entry'] );
		$this->assertNull( $array['original_document_number'] );
		$this->assertNull( $array['original_document_date'] );
	}

	/**
	 * Test fluent setters for manual entry fields.
	 */
	public function test_fluent_setters_manual_entry(): void {
		$credit_note = new CreditNote();
		$date        = new \DateTimeImmutable( '2024-08-10' );

		$result = $credit_note
			->setManualEntry( true )
			->setOriginalDocumentNumber( 'LEGACY/2024/001' )
			->setOriginalDocumentDate( $date );

		$this->assertSame( $credit_note, $result );
		$this->assertTrue( $credit_note->isManualEntry() );
		$this->assertEquals( 'LEGACY/2024/001', $credit_note->getOriginalDocumentNumber() );
		$this->assertEquals( $date, $credit_note->getOriginalDocumentDate() );
	}
}
