<?php
/**
 * Invoice Email.
 *
 * Email sent to customers with invoice PDF attachment.
 *
 * @package IHumbak\Invoices\Modules\Email
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Email;

/**
 * Invoice email class.
 */
class InvoiceEmail extends AbstractDocumentEmail {

	/**
	 * Get email ID.
	 *
	 * @return string
	 */
	protected function get_email_id(): string {
		return 'ihumbak_invoice_email';
	}

	/**
	 * Get email title for admin.
	 *
	 * @return string
	 */
	protected function get_email_title(): string {
		return __( 'Invoice', 'ihumbak-invoices' );
	}

	/**
	 * Get email description for admin.
	 *
	 * @return string
	 */
	protected function get_email_description(): string {
		return __( 'Invoice emails are sent to customers with the invoice PDF attached.', 'ihumbak-invoices' );
	}

	/**
	 * Get document type.
	 *
	 * @return string
	 */
	protected function get_document_type(): string {
		return 'invoice';
	}

	/**
	 * Get template name without extension.
	 *
	 * @return string
	 */
	protected function get_template_name(): string {
		return 'invoice-email';
	}

	/**
	 * Get default subject.
	 *
	 * @return string
	 */
	public function get_default_subject(): string {
		return __( 'Your invoice {document_number} from {site_title}', 'ihumbak-invoices' );
	}

	/**
	 * Get default heading.
	 *
	 * @return string
	 */
	public function get_default_heading(): string {
		return __( 'Invoice {document_number}', 'ihumbak-invoices' );
	}
}
