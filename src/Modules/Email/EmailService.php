<?php
/**
 * Email Service.
 *
 * Handles sending document emails with PDF attachments.
 *
 * @package IHumbak\Invoices\Modules\Email
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Email;

use IHumbak\Invoices\Models\Document;
use IHumbak\Invoices\Models\Invoice;
use IHumbak\Invoices\Models\Receipt;
use IHumbak\Invoices\Models\CreditNote;
use IHumbak\Invoices\Modules\PDF\PdfGenerator;
use IHumbak\Invoices\Infrastructure\Database\DocumentRepository;
use IHumbak\Invoices\Core\Plugin;

/**
 * Service for sending document emails.
 */
class EmailService {

	/**
	 * PDF generator instance (lazy loaded).
	 *
	 * @var PdfGenerator|null
	 */
	private ?PdfGenerator $pdf_generator = null;

	/**
	 * Document repository instance.
	 *
	 * @var DocumentRepository|null
	 */
	private ?DocumentRepository $document_repository = null;

	/**
	 * Constructor.
	 *
	 * Dependencies are lazy-loaded to avoid circular dependency during Plugin initialization.
	 *
	 * @param PdfGenerator|null       $pdf_generator       PDF generator instance.
	 * @param DocumentRepository|null $document_repository Document repository instance.
	 */
	public function __construct(
		?PdfGenerator $pdf_generator = null,
		?DocumentRepository $document_repository = null
	) {
		// Store injected dependencies if provided, otherwise lazy-load on first use.
		$this->pdf_generator       = $pdf_generator;
		$this->document_repository = $document_repository;
	}

	/**
	 * Get PDF generator instance (lazy loaded).
	 *
	 * @return PdfGenerator
	 */
	private function getPdfGenerator(): PdfGenerator {
		if ( null === $this->pdf_generator ) {
			$this->pdf_generator = Plugin::get_instance()->container()->get( 'pdf.generator' );
		}
		return $this->pdf_generator;
	}

	/**
	 * Get document repository instance (lazy loaded).
	 *
	 * @return DocumentRepository
	 */
	private function getDocumentRepository(): DocumentRepository {
		if ( null === $this->document_repository ) {
			$this->document_repository = new DocumentRepository();
		}
		return $this->document_repository;
	}

	/**
	 * Send email with document PDF attachment.
	 *
	 * @param Document $document The document to send.
	 * @return bool True if email was sent successfully.
	 */
	public function send( Document $document ): bool {
		if ( ! $this->canSend( $document ) ) {
			return false;
		}

		$recipient = $this->getRecipientEmail( $document );

		if ( empty( $recipient ) ) {
			/**
			 * Fires when email cannot be sent due to missing recipient.
			 *
			 * @param Document $document The document.
			 * @param string   $error    Error message.
			 */
			do_action( 'ihumbak_email_failed', $document, __( 'No recipient email address found.', 'ihumbak-invoices' ) );
			return false;
		}

		/**
		 * Fires before sending document email.
		 *
		 * @param Document $document  The document.
		 * @param string   $recipient Recipient email.
		 */
		do_action( 'ihumbak_before_email_send', $document, $recipient );

		// Get the appropriate WC_Email instance.
		$email = $this->getEmailInstance( $document );

		if ( ! $email ) {
			do_action( 'ihumbak_email_failed', $document, __( 'Email class not found.', 'ihumbak-invoices' ) );
			return false;
		}

		// Trigger the email.
		$result = $email->trigger( $document->getId(), $document );

		if ( $result ) {
			/**
			 * Fires after document email was sent successfully.
			 *
			 * @param Document $document  The document.
			 * @param string   $recipient Recipient email.
			 */
			do_action( 'ihumbak_email_sent', $document, $recipient );

			// Update document status to sent.
			$this->updateDocumentStatus( $document );
		} else {
			do_action( 'ihumbak_email_failed', $document, __( 'Email sending failed.', 'ihumbak-invoices' ) );
		}

		return $result;
	}

	/**
	 * Check if document can be sent via email.
	 *
	 * @param Document $document The document.
	 * @return bool True if document can be sent.
	 */
	public function canSend( Document $document ): bool {
		// Document must have an ID.
		if ( ! $document->getId() ) {
			return false;
		}

		// Document must not be a draft or cancelled.
		if ( $document->isDraft() || $document->isCancelled() ) {
			return false;
		}

		return true;
	}

	/**
	 * Get recipient email address for document.
	 *
	 * Uses billing email from the linked WooCommerce order, or falls back
	 * to the buyer email when no order is linked (for manual documents).
	 *
	 * @param Document $document The document.
	 * @return string|null Recipient email or null if not found.
	 */
	public function getRecipientEmail( Document $document ): ?string {
		$order_id = $document->getOrderId();
		$order    = null;
		$email    = null;

		// Try to get email from linked order first.
		if ( $order_id ) {
			$order = wc_get_order( $order_id );

			if ( $order ) {
				$email = $order->get_billing_email();
			}
		}

		// Fallback to buyer email if no order email found.
		if ( empty( $email ) ) {
			$buyer = $document->getBuyer();
			if ( $buyer ) {
				$email = $buyer->getEmail();
			}
		}

		if ( empty( $email ) ) {
			return null;
		}

		/**
		 * Filter the recipient email address for document emails.
		 *
		 * @param string        $email    Recipient email.
		 * @param Document      $document The document.
		 * @param \WC_Order|null $order   The WooCommerce order or null for manual documents.
		 */
		return apply_filters( 'ihumbak_email_recipient', $email, $document, $order );
	}

	/**
	 * Get WC_Email instance for document type.
	 *
	 * @param Document $document The document.
	 * @return AbstractDocumentEmail|null Email instance or null.
	 */
	private function getEmailInstance( Document $document ): ?AbstractDocumentEmail {
		$emails = WC()->mailer()->get_emails();

		$email_class = match ( $document->getDocumentType() ) {
			'invoice'        => 'IHumbak_Invoice_Email',
			'receipt'        => 'IHumbak_Receipt_Email',
			'credit_note'    => 'IHumbak_Credit_Note_Email',
			'receipt_return' => 'IHumbak_Receipt_Return_Email',
			default          => null,
		};

		if ( ! $email_class || ! isset( $emails[ $email_class ] ) ) {
			return null;
		}

		$email = $emails[ $email_class ];

		// Verify the email is our custom type.
		if ( ! $email instanceof AbstractDocumentEmail ) {
			return null;
		}

		return $email;
	}

	/**
	 * Update document after successful email send.
	 *
	 * Sets sent_at timestamp and updates status to 'sent' if currently 'issued'.
	 *
	 * @param Document $document The document.
	 * @return void
	 */
	private function updateDocumentStatus( Document $document ): void {
		// Always update sent_at timestamp.
		$document->setSentAt( new \DateTimeImmutable() );

		// Only update status if current status is 'issued'.
		if ( Document::STATUS_ISSUED === $document->getStatus() ) {
			$document->setStatus( Document::STATUS_SENT );
		}

		$this->getDocumentRepository()->save( $document );
	}

	/**
	 * Generate PDF attachment file path.
	 *
	 * Creates a temporary PDF file for email attachment.
	 *
	 * @param Document $document The document.
	 * @return string|null Path to temporary PDF file or null on failure.
	 */
	public function generatePdfAttachment( Document $document ): ?string {
		try {
			$pdf_content = $this->getPdfGenerator()->generateContent( $document );

			if ( empty( $pdf_content ) ) {
				return null;
			}

			// Create temp file.
			$temp_file = wp_tempnam( 'ihumbak_' . $document->getDocumentType() . '_' );

			if ( ! $temp_file ) {
				return null;
			}

			// Write PDF content.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Temp file for email attachment.
			$written = file_put_contents( $temp_file, $pdf_content );

			if ( false === $written ) {
				return null;
			}

			// Rename to .pdf extension.
			$pdf_file = $temp_file . '.pdf';
			// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Renaming temp file.
			rename( $temp_file, $pdf_file );

			return $pdf_file;

		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * Get PDF filename for attachment.
	 *
	 * @param Document $document The document.
	 * @return string Filename.
	 */
	public function getPdfFilename( Document $document ): string {
		$type   = $document->getDocumentType();
		$number = $document->getDocumentNumber();

		// Sanitize document number for filename.
		$safe_number = preg_replace( '/[^a-zA-Z0-9_-]/', '_', $number );

		return sprintf( '%s_%s.pdf', $type, $safe_number );
	}

	/**
	 * Check if auto-send is enabled for document type.
	 *
	 * @param string $document_type Document type (invoice, receipt, credit_note).
	 * @return bool True if auto-send is enabled.
	 */
	public function isAutoSendEnabled( string $document_type ): bool {
		$settings = Plugin::get_instance()->get_settings();
		$key      = 'auto_send_' . $document_type;

		return ! empty( $settings['email'][ $key ] );
	}

	/**
	 * Send document email if auto-send is enabled.
	 *
	 * Called from ihumbak_document_issued action.
	 *
	 * @param Document $document The document.
	 * @return bool True if email was sent.
	 */
	public function maybeSendOnIssue( Document $document ): bool {
		$type = $document->getDocumentType();

		if ( ! $this->isAutoSendEnabled( $type ) ) {
			return false;
		}

		return $this->send( $document );
	}
}
