<?php
/**
 * Credit Note Email.
 *
 * Email sent to customers with credit note PDF attachment.
 *
 * @package IHumbak\Invoices\Modules\Email
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Email;

/**
 * Credit note email class.
 */
class CreditNoteEmail extends AbstractDocumentEmail {

	/**
	 * Get email ID.
	 *
	 * @return string
	 */
	protected function get_email_id(): string {
		return 'ihumbak_credit_note_email';
	}

	/**
	 * Get email title for admin.
	 *
	 * @return string
	 */
	protected function get_email_title(): string {
		return __( 'Credit Note', 'ihumbak-invoices' );
	}

	/**
	 * Get email description for admin.
	 *
	 * @return string
	 */
	protected function get_email_description(): string {
		return __( 'Credit note emails are sent to customers with the credit note PDF attached.', 'ihumbak-invoices' );
	}

	/**
	 * Get document type.
	 *
	 * @return string
	 */
	protected function get_document_type(): string {
		return 'credit_note';
	}

	/**
	 * Get template name without extension.
	 *
	 * @return string
	 */
	protected function get_template_name(): string {
		return 'credit-note-email';
	}

	/**
	 * Get default subject.
	 *
	 * @return string
	 */
	public function get_default_subject(): string {
		return __( 'Your credit note {document_number} from {site_title}', 'ihumbak-invoices' );
	}

	/**
	 * Get default heading.
	 *
	 * @return string
	 */
	public function get_default_heading(): string {
		return __( 'Credit Note {document_number}', 'ihumbak-invoices' );
	}
}
