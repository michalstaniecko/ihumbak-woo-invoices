<?php
/**
 * Abstract Document Email.
 *
 * Base class for all document email types.
 *
 * @package IHumbak\Invoices\Modules\Email
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Email;

use IHumbak\Invoices\Models\Document;
use IHumbak\Invoices\Infrastructure\Database\DocumentRepository;
use IHumbak\Invoices\Infrastructure\Database\DocumentItemRepository;
use IHumbak\Invoices\Core\Plugin;

/**
 * Abstract base class for document emails.
 */
abstract class AbstractDocumentEmail extends \WC_Email {

	/**
	 * Document being sent.
	 *
	 * @var Document|null
	 */
	protected ?Document $document = null;

	/**
	 * Document repository.
	 *
	 * @var DocumentRepository
	 */
	protected DocumentRepository $document_repository;

	/**
	 * Document item repository.
	 *
	 * @var DocumentItemRepository
	 */
	protected DocumentItemRepository $item_repository;

	/**
	 * Email service.
	 *
	 * @var EmailService
	 */
	protected EmailService $email_service;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->document_repository = new DocumentRepository();
		$this->item_repository     = new DocumentItemRepository();
		$this->email_service       = new EmailService();

		// Email slug, used for saving settings.
		$this->id = $this->get_email_id();

		// Translators: This is the email title in admin.
		$this->title = $this->get_email_title();

		// Description shown in admin.
		$this->description = $this->get_email_description();

		// Template paths.
		$this->template_html  = $this->get_template_name() . '.php';
		$this->template_plain = 'plain/' . $this->get_template_name() . '.php';
		$this->template_base  = IHUMBAK_INVOICES_PATH . 'templates/emails/';

		// Placeholders.
		$this->placeholders = array(
			'{document_number}' => '',
			'{order_number}'    => '',
			'{site_title}'      => $this->get_blogname(),
		);

		// Triggers.
		add_action( 'ihumbak_send_' . $this->get_document_type() . '_email', array( $this, 'trigger' ), 10, 2 );

		// Call parent constructor.
		parent::__construct();

		// Other settings.
		$this->recipient = $this->get_option( 'recipient', '' );
	}

	/**
	 * Get email ID.
	 *
	 * @return string
	 */
	abstract protected function get_email_id(): string;

	/**
	 * Get email title for admin.
	 *
	 * @return string
	 */
	abstract protected function get_email_title(): string;

	/**
	 * Get email description for admin.
	 *
	 * @return string
	 */
	abstract protected function get_email_description(): string;

	/**
	 * Get document type.
	 *
	 * @return string
	 */
	abstract protected function get_document_type(): string;

	/**
	 * Get template name without extension.
	 *
	 * @return string
	 */
	abstract protected function get_template_name(): string;

	/**
	 * Get default subject.
	 *
	 * @return string
	 */
	abstract public function get_default_subject(): string;

	/**
	 * Get default heading.
	 *
	 * @return string
	 */
	abstract public function get_default_heading(): string;

	/**
	 * Trigger the email.
	 *
	 * @param int|null      $document_id Document ID.
	 * @param Document|null $document    Document object (optional).
	 * @return bool True if email was sent.
	 */
	public function trigger( $document_id, $document = null ): bool {
		$this->setup_locale();

		if ( $document instanceof Document ) {
			$this->document = $document;
		} elseif ( $document_id ) {
			$this->document = $this->document_repository->find( (int) $document_id );
		}

		if ( ! $this->document ) {
			$this->restore_locale();
			return false;
		}

		// Load document items if not already loaded.
		if ( empty( $this->document->getItems() ) ) {
			$items = $this->item_repository->findByDocumentId( $this->document->getId() );
			$this->document->setItems( $items );
		}

		// Set placeholders.
		$this->placeholders['{document_number}'] = $this->document->getDocumentNumber();

		if ( $this->document->getOrderId() ) {
			$order = wc_get_order( $this->document->getOrderId() );
			if ( $order ) {
				$this->placeholders['{order_number}'] = $order->get_order_number();
			}
		}

		// Get recipient.
		$this->recipient = $this->email_service->getRecipientEmail( $this->document );

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			$this->restore_locale();
			return false;
		}

		$result = $this->send(
			$this->get_recipient(),
			$this->get_subject(),
			$this->get_content(),
			$this->get_headers(),
			$this->get_attachments()
		);

		$this->restore_locale();

		return $result;
	}

	/**
	 * Get email subject.
	 *
	 * @return string
	 */
	public function get_subject(): string {
		$subject = $this->get_option( 'subject', $this->get_default_subject() );
		return $this->format_string( $subject );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 */
	public function get_heading(): string {
		$heading = $this->get_option( 'heading', $this->get_default_heading() );
		return $this->format_string( $heading );
	}

	/**
	 * Get content HTML.
	 *
	 * @return string
	 */
	public function get_content_html(): string {
		return wc_get_template_html(
			$this->template_html,
			array(
				'document'      => $this->document,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => false,
				'plain_text'    => false,
				'email'         => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Get content plain text.
	 *
	 * @return string
	 */
	public function get_content_plain(): string {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'document'      => $this->document,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => false,
				'plain_text'    => true,
				'email'         => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Get email attachments.
	 *
	 * @return array Attachment file paths.
	 */
	public function get_attachments(): array {
		$attachments = parent::get_attachments();

		if ( $this->document ) {
			$pdf_path = $this->email_service->generatePdfAttachment( $this->document );

			if ( $pdf_path && file_exists( $pdf_path ) ) {
				$attachments[] = $pdf_path;

				// Schedule cleanup of temp file.
				add_action(
					'shutdown',
					function () use ( $pdf_path ) {
						if ( file_exists( $pdf_path ) ) {
							// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Cleanup temp file.
							unlink( $pdf_path );
						}
					}
				);
			}
		}

		/**
		 * Filter email attachments.
		 *
		 * @param array    $attachments Attachment file paths.
		 * @param Document $document    The document.
		 * @param self     $email       Email instance.
		 */
		return apply_filters( 'ihumbak_email_attachments', $attachments, $this->document, $this );
	}

	/**
	 * Get the document.
	 *
	 * @return Document|null
	 */
	public function get_document(): ?Document {
		return $this->document;
	}

	/**
	 * Initialize form fields for admin settings.
	 *
	 * @return void
	 */
	public function init_form_fields(): void {
		/* translators: %s: list of placeholders */
		$placeholder_text = sprintf(
			__( 'Available placeholders: %s', 'ihumbak-invoices' ),
			'<code>{document_number}</code>, <code>{order_number}</code>, <code>{site_title}</code>'
		);

		$this->form_fields = array(
			'enabled'    => array(
				'title'   => __( 'Enable/Disable', 'ihumbak-invoices' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'ihumbak-invoices' ),
				'default' => 'yes',
			),
			'subject'    => array(
				'title'       => __( 'Subject', 'ihumbak-invoices' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => $placeholder_text,
				'placeholder' => $this->get_default_subject(),
				'default'     => '',
			),
			'heading'    => array(
				'title'       => __( 'Email heading', 'ihumbak-invoices' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => $placeholder_text,
				'placeholder' => $this->get_default_heading(),
				'default'     => '',
			),
			'email_type' => array(
				'title'       => __( 'Email type', 'ihumbak-invoices' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'ihumbak-invoices' ),
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_email_type_options(),
				'desc_tip'    => true,
			),
		);
	}
}
