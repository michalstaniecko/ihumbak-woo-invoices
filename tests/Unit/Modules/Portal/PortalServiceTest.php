<?php
/**
 * PortalService unit tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Modules\Portal
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Modules\Portal;

use IHumbak\Invoices\Infrastructure\Database\DocumentRepository;
use IHumbak\Invoices\Models\Document;
use IHumbak\Invoices\Models\Invoice;
use IHumbak\Invoices\Models\Receipt;
use IHumbak\Invoices\Models\CreditNote;
use IHumbak\Invoices\Modules\Portal\PortalService;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PortalService.
 */
class PortalServiceTest extends TestCase {

	/**
	 * Mock document repository.
	 *
	 * @var DocumentRepository|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $repository_mock;

	/**
	 * Service under test.
	 *
	 * @var PortalService
	 */
	private PortalService $service;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->repository_mock = $this->createMock( DocumentRepository::class );
		$this->service         = new PortalService( $this->repository_mock );
	}

	// ==========================================================================
	// getDocumentsForOrder() tests
	// ==========================================================================

	/**
	 * Test getDocumentsForOrder returns empty array when no documents exist.
	 */
	public function test_get_documents_for_order_returns_empty_when_no_documents(): void {
		$this->repository_mock->method( 'findByOrderId' )
			->with( 123 )
			->willReturn( array() );

		$result = $this->service->getDocumentsForOrder( 123 );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test getDocumentsForOrder filters out draft documents.
	 */
	public function test_get_documents_for_order_filters_out_drafts(): void {
		$draft_invoice = Invoice::fromArray(
			array(
				'id'              => 1,
				'order_id'        => 123,
				'document_type'   => 'invoice',
				'document_number' => '',
				'status'          => Document::STATUS_DRAFT,
			)
		);

		$issued_invoice = Invoice::fromArray(
			array(
				'id'              => 2,
				'order_id'        => 123,
				'document_type'   => 'invoice',
				'document_number' => 'FV/2024/12/0001',
				'status'          => Document::STATUS_ISSUED,
			)
		);

		$this->repository_mock->method( 'findByOrderId' )
			->with( 123 )
			->willReturn( array( $draft_invoice, $issued_invoice ) );

		$result = $this->service->getDocumentsForOrder( 123 );

		$this->assertCount( 1, $result );
		$this->assertSame( 2, $result[0]->getId() );
	}

	/**
	 * Test getDocumentsForOrder filters out cancelled documents.
	 */
	public function test_get_documents_for_order_filters_out_cancelled(): void {
		$cancelled_invoice = Invoice::fromArray(
			array(
				'id'              => 1,
				'order_id'        => 123,
				'document_type'   => 'invoice',
				'document_number' => 'FV/2024/12/0001',
				'status'          => Document::STATUS_CANCELLED,
			)
		);

		$issued_invoice = Invoice::fromArray(
			array(
				'id'              => 2,
				'order_id'        => 123,
				'document_type'   => 'invoice',
				'document_number' => 'FV/2024/12/0002',
				'status'          => Document::STATUS_ISSUED,
			)
		);

		$this->repository_mock->method( 'findByOrderId' )
			->with( 123 )
			->willReturn( array( $cancelled_invoice, $issued_invoice ) );

		$result = $this->service->getDocumentsForOrder( 123 );

		$this->assertCount( 1, $result );
		$this->assertSame( 2, $result[0]->getId() );
	}

	/**
	 * Test getDocumentsForOrder keeps issued, sent, and paid documents.
	 */
	public function test_get_documents_for_order_keeps_visible_statuses(): void {
		$issued_invoice = Invoice::fromArray(
			array(
				'id'              => 1,
				'order_id'        => 123,
				'document_type'   => 'invoice',
				'document_number' => 'FV/2024/12/0001',
				'status'          => Document::STATUS_ISSUED,
			)
		);

		$sent_invoice = Invoice::fromArray(
			array(
				'id'              => 2,
				'order_id'        => 123,
				'document_type'   => 'invoice',
				'document_number' => 'FV/2024/12/0002',
				'status'          => Document::STATUS_SENT,
			)
		);

		$paid_invoice = Invoice::fromArray(
			array(
				'id'              => 3,
				'order_id'        => 123,
				'document_type'   => 'invoice',
				'document_number' => 'FV/2024/12/0003',
				'status'          => Document::STATUS_PAID,
			)
		);

		$this->repository_mock->method( 'findByOrderId' )
			->with( 123 )
			->willReturn( array( $issued_invoice, $sent_invoice, $paid_invoice ) );

		$result = $this->service->getDocumentsForOrder( 123 );

		$this->assertCount( 3, $result );
	}

	/**
	 * Test getDocumentsForOrder handles different document types.
	 */
	public function test_get_documents_for_order_handles_different_types(): void {
		$invoice = Invoice::fromArray(
			array(
				'id'              => 1,
				'order_id'        => 123,
				'document_type'   => 'invoice',
				'document_number' => 'FV/2024/12/0001',
				'status'          => Document::STATUS_ISSUED,
			)
		);

		$receipt = Receipt::fromArray(
			array(
				'id'              => 2,
				'order_id'        => 123,
				'document_type'   => 'receipt',
				'document_number' => 'PAR/2024/12/0001',
				'status'          => Document::STATUS_ISSUED,
			)
		);

		$credit_note = CreditNote::fromArray(
			array(
				'id'              => 3,
				'order_id'        => 123,
				'document_type'   => 'credit_note',
				'document_number' => 'CN/2024/12/0001',
				'status'          => Document::STATUS_ISSUED,
			)
		);

		$this->repository_mock->method( 'findByOrderId' )
			->with( 123 )
			->willReturn( array( $invoice, $receipt, $credit_note ) );

		$result = $this->service->getDocumentsForOrder( 123 );

		$this->assertCount( 3, $result );
		$this->assertInstanceOf( Invoice::class, $result[0] );
		$this->assertInstanceOf( Receipt::class, $result[1] );
		$this->assertInstanceOf( CreditNote::class, $result[2] );
	}

	// ==========================================================================
	// canCustomerAccessDocument() tests - basic cases
	// ==========================================================================

	/**
	 * Test canCustomerAccessDocument returns false when document not found.
	 */
	public function test_can_customer_access_document_returns_false_when_document_not_found(): void {
		$this->repository_mock->method( 'find' )
			->with( 999 )
			->willReturn( null );

		$result = $this->service->canCustomerAccessDocument( 1, 999 );

		$this->assertFalse( $result );
	}

	/**
	 * Test canCustomerAccessDocument returns false for draft documents.
	 */
	public function test_can_customer_access_document_returns_false_for_draft(): void {
		$draft_invoice = Invoice::fromArray(
			array(
				'id'              => 1,
				'order_id'        => 123,
				'document_type'   => 'invoice',
				'document_number' => '',
				'status'          => Document::STATUS_DRAFT,
			)
		);

		$this->repository_mock->method( 'find' )
			->with( 1 )
			->willReturn( $draft_invoice );

		$result = $this->service->canCustomerAccessDocument( 1, 1 );

		$this->assertFalse( $result );
	}

	/**
	 * Test canCustomerAccessDocument returns false for cancelled documents.
	 */
	public function test_can_customer_access_document_returns_false_for_cancelled(): void {
		$cancelled_invoice = Invoice::fromArray(
			array(
				'id'              => 1,
				'order_id'        => 123,
				'document_type'   => 'invoice',
				'document_number' => 'FV/2024/12/0001',
				'status'          => Document::STATUS_CANCELLED,
			)
		);

		$this->repository_mock->method( 'find' )
			->with( 1 )
			->willReturn( $cancelled_invoice );

		$result = $this->service->canCustomerAccessDocument( 1, 1 );

		$this->assertFalse( $result );
	}

	/**
	 * Test canCustomerAccessDocument returns false when document has no order.
	 */
	public function test_can_customer_access_document_returns_false_when_no_order(): void {
		$invoice = Invoice::fromArray(
			array(
				'id'              => 1,
				'order_id'        => null,
				'document_type'   => 'invoice',
				'document_number' => 'FV/2024/12/0001',
				'status'          => Document::STATUS_ISSUED,
			)
		);

		$this->repository_mock->method( 'find' )
			->with( 1 )
			->willReturn( $invoice );

		$result = $this->service->canCustomerAccessDocument( 1, 1 );

		$this->assertFalse( $result );
	}

	// ==========================================================================
	// getDocumentForCustomer() tests
	// ==========================================================================

	/**
	 * Test getDocumentForCustomer returns null when customer cannot access.
	 */
	public function test_get_document_for_customer_returns_null_when_no_access(): void {
		$this->repository_mock->method( 'find' )
			->with( 999 )
			->willReturn( null );

		$result = $this->service->getDocumentForCustomer( 1, 999 );

		$this->assertNull( $result );
	}

	/**
	 * Test getDocumentForCustomer returns null for draft document.
	 */
	public function test_get_document_for_customer_returns_null_for_draft(): void {
		$draft_invoice = Invoice::fromArray(
			array(
				'id'              => 1,
				'order_id'        => 123,
				'document_type'   => 'invoice',
				'document_number' => '',
				'status'          => Document::STATUS_DRAFT,
			)
		);

		$this->repository_mock->method( 'find' )
			->with( 1 )
			->willReturn( $draft_invoice );

		$result = $this->service->getDocumentForCustomer( 1, 1 );

		$this->assertNull( $result );
	}

	// ==========================================================================
	// Visible statuses tests
	// ==========================================================================

	/**
	 * Test only issued, sent, and paid are visible.
	 *
	 * @dataProvider visibleStatusProvider
	 *
	 * @param string $status   Document status.
	 * @param bool   $expected Expected visibility.
	 */
	public function test_visibility_by_status( string $status, bool $expected ): void {
		$invoice = Invoice::fromArray(
			array(
				'id'              => 1,
				'order_id'        => 123,
				'document_type'   => 'invoice',
				'document_number' => 'FV/2024/12/0001',
				'status'          => $status,
			)
		);

		$this->repository_mock->method( 'findByOrderId' )
			->with( 123 )
			->willReturn( array( $invoice ) );

		$result = $this->service->getDocumentsForOrder( 123 );

		if ( $expected ) {
			$this->assertCount( 1, $result );
		} else {
			$this->assertEmpty( $result );
		}
	}

	/**
	 * Data provider for visibility by status tests.
	 *
	 * @return array<string, array{string, bool}>
	 */
	public static function visibleStatusProvider(): array {
		return array(
			'draft is hidden'     => array( Document::STATUS_DRAFT, false ),
			'issued is visible'   => array( Document::STATUS_ISSUED, true ),
			'sent is visible'     => array( Document::STATUS_SENT, true ),
			'paid is visible'     => array( Document::STATUS_PAID, true ),
			'cancelled is hidden' => array( Document::STATUS_CANCELLED, false ),
		);
	}

	// ==========================================================================
	// Mixed documents filtering tests
	// ==========================================================================

	/**
	 * Test filtering works correctly with mixed statuses.
	 */
	public function test_filtering_with_mixed_statuses(): void {
		$documents = array(
			Invoice::fromArray(
				array(
					'id'              => 1,
					'order_id'        => 123,
					'document_type'   => 'invoice',
					'document_number' => '',
					'status'          => Document::STATUS_DRAFT,
				)
			),
			Invoice::fromArray(
				array(
					'id'              => 2,
					'order_id'        => 123,
					'document_type'   => 'invoice',
					'document_number' => 'FV/2024/12/0001',
					'status'          => Document::STATUS_ISSUED,
				)
			),
			Receipt::fromArray(
				array(
					'id'              => 3,
					'order_id'        => 123,
					'document_type'   => 'receipt',
					'document_number' => 'PAR/2024/12/0001',
					'status'          => Document::STATUS_SENT,
				)
			),
			Invoice::fromArray(
				array(
					'id'              => 4,
					'order_id'        => 123,
					'document_type'   => 'invoice',
					'document_number' => 'FV/2024/12/0002',
					'status'          => Document::STATUS_CANCELLED,
				)
			),
			CreditNote::fromArray(
				array(
					'id'              => 5,
					'order_id'        => 123,
					'document_type'   => 'credit_note',
					'document_number' => 'CN/2024/12/0001',
					'status'          => Document::STATUS_PAID,
				)
			),
		);

		$this->repository_mock->method( 'findByOrderId' )
			->with( 123 )
			->willReturn( $documents );

		$result = $this->service->getDocumentsForOrder( 123 );

		$this->assertCount( 3, $result );

		$ids = array_map( fn( Document $d ) => $d->getId(), $result );
		$this->assertContains( 2, $ids );
		$this->assertContains( 3, $ids );
		$this->assertContains( 5, $ids );
		$this->assertNotContains( 1, $ids );
		$this->assertNotContains( 4, $ids );
	}

	/**
	 * Test filtered array is reindexed.
	 */
	public function test_filtered_array_is_reindexed(): void {
		$documents = array(
			Invoice::fromArray(
				array(
					'id'              => 1,
					'order_id'        => 123,
					'document_type'   => 'invoice',
					'document_number' => '',
					'status'          => Document::STATUS_DRAFT,
				)
			),
			Invoice::fromArray(
				array(
					'id'              => 2,
					'order_id'        => 123,
					'document_type'   => 'invoice',
					'document_number' => 'FV/2024/12/0001',
					'status'          => Document::STATUS_ISSUED,
				)
			),
		);

		$this->repository_mock->method( 'findByOrderId' )
			->with( 123 )
			->willReturn( $documents );

		$result = $this->service->getDocumentsForOrder( 123 );

		// Array should be reindexed (keys should be 0, 1, 2, ... not original indices).
		$this->assertArrayHasKey( 0, $result );
		$this->assertArrayNotHasKey( 1, $result );
	}
}
