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
use IHumbak\Invoices\Infrastructure\Traits\SiteLocaleTrait;
use IHumbak\Invoices\Core\Plugin;

/**
 * Abstract base class for document emails.
 */
abstract class AbstractDocumentEmail extends \WC_Email {

	use SiteLocaleTrait;

	/**
	 * Document being sent.
	 *
	 * @var Document|null
	 */
	protected ?Document $document = null;

	/**
	 * Document repository (lazy loaded).
	 *
	 * @var DocumentRepository|null
	 */
	protected ?DocumentRepository $document_repository = null;

	/**
	 * Document item repository (lazy loaded).
	 *
	 * @var DocumentItemRepository|null
	 */
	protected ?DocumentItemRepository $item_repository = null;

	/**
	 * Email service (lazy loaded).
	 *
	 * @var EmailService|null
	 */
	protected ?EmailService $email_service = null;

	/**
	 * Constructor.
	 *
	 * Dependencies are lazy-loaded to avoid circular dependency during Plugin initialization.
	 */
	public function __construct() {
		// Dependencies are lazy-loaded on first use to avoid circular dependencies.

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

		// Triggers - wrap trigger() to avoid PHPStan warning about returning bool from action callback.
		add_action(
			'ihumbak_send_' . $this->get_document_type() . '_email',
			function ( $document_id, $document = null ): void {
				$this->trigger( $document_id, $document );
			},
			10,
			2
		);

		// Call parent constructor.
		parent::__construct();

		// Other settings.
		$this->recipient = $this->get_option( 'recipient', '' );
	}

	/**
	 * Get document repository (lazy loaded).
	 *
	 * @return DocumentRepository
	 */
	protected function getDocumentRepository(): DocumentRepository {
		if ( null === $this->document_repository ) {
			$this->document_repository = new DocumentRepository();
		}
		return $this->document_repository;
	}

	/**
	 * Get document item repository (lazy loaded).
	 *
	 * @return DocumentItemRepository
	 */
	protected function getItemRepository(): DocumentItemRepository {
		if ( null === $this->item_repository ) {
			$this->item_repository = new DocumentItemRepository();
		}
		return $this->item_repository;
	}

	/**
	 * Get email service (lazy loaded).
	 *
	 * @return EmailService
	 */
	protected function getEmailService(): EmailService {
		if ( null === $this->email_service ) {
			$this->email_service = Plugin::get_instance()->getEmailService() ?? new EmailService();
		}
		return $this->email_service;
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
	 * Override in child classes to provide document-specific subject.
	 *
	 * @return string
	 */
	public function get_default_subject(): string {
		return __( 'Your document from {site_title}', 'ihumbak-invoices' );
	}

	/**
	 * Get default heading.
	 *
	 * Override in child classes to provide document-specific heading.
	 *
	 * @return string
	 */
	public function get_default_heading(): string {
		return __( 'Document', 'ihumbak-invoices' );
	}

	/**
	 * Trigger the email.
	 *
	 * @param int|null      $document_id Document ID.
	 * @param Document|null $document    Document object (optional).
	 * @return bool True if email was sent.
	 */
	public function trigger( $document_id, $document = null ): bool {
		// Switch to site locale for email content.
		// This ensures email uses site language instead of admin user language.
		$locale_switched = $this->switchToSiteLocale( 'ihumbak_email_locale' );

		try {
			if ( $document instanceof Document ) {
				$this->document = $document;
			} elseif ( $document_id ) {
				$this->document = $this->getDocumentRepository()->find( (int) $document_id );
			}

			if ( ! $this->document ) {
				return false;
			}

			// Load document items if not already loaded.
			if ( empty( $this->document->getItems() ) ) {
				$items = $this->getItemRepository()->findByDocumentId( $this->document->getId() );
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
			$this->recipient = $this->getEmailService()->getRecipientEmail( $this->document );

			if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
				return false;
			}

			return $this->send(
				$this->get_recipient(),
				$this->get_subject(),
				$this->get_content(),
				$this->get_headers(),
				$this->get_attachments()
			);
		} finally {
			// Always restore locale, even if an exception occurs.
			if ( $locale_switched ) {
				$this->restoreLocale();
			}
		}
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
		// Use sample document for preview if no real document is set.
		$document = $this->document ?? $this->get_sample_document();

		return wc_get_template_html(
			$this->template_html,
			array(
				'document'      => $document,
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
		// Use sample document for preview if no real document is set.
		$document = $this->document ?? $this->get_sample_document();

		return wc_get_template_html(
			$this->template_plain,
			array(
				'document'      => $document,
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
	 * Get a sample document for email preview.
	 *
	 * Creates a mock document with sample data for WooCommerce email preview functionality.
	 *
	 * @return Document
	 */
	protected function get_sample_document(): Document {
		$document_type = $this->get_document_type();

		$sample_data = array(
			'id'              => 0,
			'document_number' => 'SAMPLE-2025/01/0001',
			'status'          => Document::STATUS_ISSUED,
			'issue_date'      => gmdate( 'Y-m-d' ),
			'sale_date'       => gmdate( 'Y-m-d' ),
			'due_date'        => gmdate( 'Y-m-d', strtotime( '+14 days' ) ),
			'currency'        => get_woocommerce_currency(),
			'total_net'       => 100.00,
			'total_tax'       => 23.00,
			'total'           => 123.00,
			'buyer_name'      => __( 'Sample Customer', 'ihumbak-invoices' ),
			'buyer_address'   => __( '123 Sample Street, Sample City', 'ihumbak-invoices' ),
			'payment_method'  => 'transfer',
		);

		// Create document based on type.
		switch ( $document_type ) {
			case 'invoice':
				return \IHumbak\Invoices\Models\Invoice::fromArray( $sample_data );
			case 'receipt':
				return \IHumbak\Invoices\Models\Receipt::fromArray( $sample_data );
			case 'credit_note':
				return \IHumbak\Invoices\Models\CreditNote::fromArray( $sample_data );
			default:
				return \IHumbak\Invoices\Models\Invoice::fromArray( $sample_data );
		}
	}

	/**
	 * Get email attachments.
	 *
	 * @return array<string> Attachment file paths.
	 */
	public function get_attachments(): array {
		$attachments = parent::get_attachments();

		if ( $this->document ) {
			$pdf_path = $this->getEmailService()->generatePdfAttachment( $this->document );

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
		$placeholder_text = sprintf(
			/* translators: %s: list of available placeholders like {document_number}, {order_number}, {site_title} */
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
