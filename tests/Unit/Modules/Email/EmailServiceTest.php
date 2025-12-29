<?php
/**
 * EmailService unit tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Modules\Email
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Modules\Email;

use IHumbak\Invoices\Modules\Email\EmailService;
use IHumbak\Invoices\Models\Invoice;
use IHumbak\Invoices\Models\Receipt;
use IHumbak\Invoices\Models\CreditNote;
use IHumbak\Invoices\Models\Document;
use PHPUnit\Framework\TestCase;

/**
 * Tests for EmailService.
 */
class EmailServiceTest extends TestCase {

	/**
	 * Test canSend returns false for document without ID.
	 */
	public function test_can_send_returns_false_for_document_without_id(): void {
		$service  = $this->createEmailServiceWithMocks();
		$document = new Invoice();

		$this->assertFalse( $service->canSend( $document ) );
	}

	/**
	 * Test canSend returns false for draft document.
	 */
	public function test_can_send_returns_false_for_draft_document(): void {
		$service  = $this->createEmailServiceWithMocks();
		$document = new Invoice();
		$document->setId( 1 );
		$document->setStatus( Document::STATUS_DRAFT );

		$this->assertFalse( $service->canSend( $document ) );
	}

	/**
	 * Test canSend returns false for cancelled document.
	 */
	public function test_can_send_returns_false_for_cancelled_document(): void {
		$service  = $this->createEmailServiceWithMocks();
		$document = new Invoice();
		$document->setId( 1 );
		$document->setStatus( Document::STATUS_CANCELLED );

		$this->assertFalse( $service->canSend( $document ) );
	}

	/**
	 * Test canSend returns true for issued document with ID.
	 */
	public function test_can_send_returns_true_for_issued_document(): void {
		$service  = $this->createEmailServiceWithMocks();
		$document = new Invoice();
		$document->setId( 1 );
		$document->setStatus( Document::STATUS_ISSUED );

		$this->assertTrue( $service->canSend( $document ) );
	}

	/**
	 * Test canSend returns true for sent document with ID.
	 */
	public function test_can_send_returns_true_for_sent_document(): void {
		$service  = $this->createEmailServiceWithMocks();
		$document = new Invoice();
		$document->setId( 1 );
		$document->setStatus( Document::STATUS_SENT );

		$this->assertTrue( $service->canSend( $document ) );
	}

	/**
	 * Test canSend returns true for paid document with ID.
	 */
	public function test_can_send_returns_true_for_paid_document(): void {
		$service  = $this->createEmailServiceWithMocks();
		$document = new Invoice();
		$document->setId( 1 );
		$document->setStatus( Document::STATUS_PAID );

		$this->assertTrue( $service->canSend( $document ) );
	}

	/**
	 * Test getPdfFilename returns correct format for invoice.
	 */
	public function test_get_pdf_filename_for_invoice(): void {
		$service  = $this->createEmailServiceWithMocks();
		$document = new Invoice();
		$document->setDocumentNumber( 'FV/2024/01/0001' );

		$filename = $service->getPdfFilename( $document );

		$this->assertStringContainsString( 'invoice', $filename );
		$this->assertStringEndsWith( '.pdf', $filename );
	}

	/**
	 * Test getPdfFilename returns correct format for receipt.
	 */
	public function test_get_pdf_filename_for_receipt(): void {
		$service  = $this->createEmailServiceWithMocks();
		$document = new Receipt();
		$document->setDocumentNumber( 'PAR/2024/01/0001' );

		$filename = $service->getPdfFilename( $document );

		$this->assertStringContainsString( 'receipt', $filename );
		$this->assertStringEndsWith( '.pdf', $filename );
	}

	/**
	 * Test getPdfFilename returns correct format for credit note.
	 */
	public function test_get_pdf_filename_for_credit_note(): void {
		$service  = $this->createEmailServiceWithMocks();
		$document = new CreditNote();
		$document->setDocumentNumber( 'CN/2024/01/0001' );

		$filename = $service->getPdfFilename( $document );

		$this->assertStringContainsString( 'credit_note', $filename );
		$this->assertStringEndsWith( '.pdf', $filename );
	}

	/**
	 * Test getPdfFilename sanitizes special characters.
	 */
	public function test_get_pdf_filename_sanitizes_special_characters(): void {
		$service  = $this->createEmailServiceWithMocks();
		$document = new Invoice();
		$document->setDocumentNumber( 'FV/2024/01/0001' );

		$filename = $service->getPdfFilename( $document );

		// Slash should be replaced with underscore.
		$this->assertStringNotContainsString( '/', $filename );
	}

	/**
	 * Test getRecipientEmail returns null when document has no order.
	 */
	public function test_get_recipient_email_returns_null_without_order(): void {
		$service  = $this->createEmailServiceWithMocks();
		$document = new Invoice();
		$document->setId( 1 );

		$email = $service->getRecipientEmail( $document );

		$this->assertNull( $email );
	}

	/**
	 * Create EmailService with mocked dependencies.
	 *
	 * @return EmailService
	 */
	private function createEmailServiceWithMocks(): EmailService {
		$pdf_generator = $this->createMock( \IHumbak\Invoices\Modules\PDF\PdfGenerator::class );
		$repository    = $this->createMock( \IHumbak\Invoices\Infrastructure\Database\DocumentRepository::class );

		return new EmailService( $pdf_generator, $repository );
	}
}
