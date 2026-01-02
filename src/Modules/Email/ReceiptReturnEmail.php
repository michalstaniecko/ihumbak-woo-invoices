<?php
/**
 * Receipt Return Email.
 *
 * Email sent to customers with receipt return PDF attachment.
 *
 * @package IHumbak\Invoices\Modules\Email
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Email;

/**
 * Receipt return email class.
 */
class ReceiptReturnEmail extends AbstractDocumentEmail {

	/**
	 * Get email ID.
	 *
	 * @return string
	 */
	protected function get_email_id(): string {
		return 'ihumbak_receipt_return_email';
	}

	/**
	 * Get email title for admin.
	 *
	 * @return string
	 */
	protected function get_email_title(): string {
		return __( 'Receipt Return', 'ihumbak-invoices' );
	}

	/**
	 * Get email description for admin.
	 *
	 * @return string
	 */
	protected function get_email_description(): string {
		return __( 'Receipt return emails are sent to customers with the receipt return PDF attached.', 'ihumbak-invoices' );
	}

	/**
	 * Get document type.
	 *
	 * @return string
	 */
	protected function get_document_type(): string {
		return 'receipt_return';
	}

	/**
	 * Get template name without extension.
	 *
	 * @return string
	 */
	protected function get_template_name(): string {
		return 'receipt-return-email';
	}

	/**
	 * Get default subject.
	 *
	 * @return string
	 */
	public function get_default_subject(): string {
		return __( 'Your receipt return {document_number} from {site_title}', 'ihumbak-invoices' );
	}

	/**
	 * Get default heading.
	 *
	 * @return string
	 */
	public function get_default_heading(): string {
		return __( 'Receipt Return {document_number}', 'ihumbak-invoices' );
	}
}
