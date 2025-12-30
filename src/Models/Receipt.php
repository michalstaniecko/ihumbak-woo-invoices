<?php
/**
 * Receipt Model.
 *
 * @package IHumbak\Invoices\Models
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Models;

/**
 * Receipt (Paragon) model.
 */
class Receipt extends Document {

	/**
	 * Document type identifier.
	 */
	public const TYPE = 'receipt';

	/**
	 * Get document type.
	 *
	 * @return string
	 */
	public function getDocumentType(): string {
		return self::TYPE;
	}

	/**
	 * Get document type label.
	 *
	 * @return string
	 */
	public function getDocumentTypeLabel(): string {
		return __( 'Receipt', 'ihumbak-invoices' );
	}

	/**
	 * Create from array.
	 *
	 * @param array<string, mixed> $data Receipt data.
	 * @return self
	 */
	public static function fromArray( array $data ): self {
		$receipt = new self();
		$receipt->hydrateFromArray( $data );

		return $receipt;
	}

	/**
	 * Convert to array for database storage.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'id'                   => $this->id,
			'order_id'             => $this->order_id,
			'document_type'        => $this->getDocumentType(),
			'document_number'      => $this->document_number,
			'issue_date'           => $this->issue_date?->format( 'Y-m-d' ),
			'sale_date'            => $this->sale_date?->format( 'Y-m-d' ),
			'due_date'             => null, // Receipts don't have due date.
			'payment_date'         => $this->payment_date?->format( 'Y-m-d' ),
			'buyer_data'           => $this->buyer?->toJson(),
			'seller_data'          => $this->seller?->toJson(),
			'subtotal'             => $this->subtotal,
			'tax_total'            => $this->tax_total,
			'total'                => $this->total,
			'currency'             => $this->currency,
			'status'               => $this->status,
			'pdf_path'             => $this->pdf_path,
			'notes'                => $this->notes,
			'payment_method'       => $this->payment_method,
			'payment_method_id'    => $this->payment_method_id,
			'payment_method_title' => $this->payment_method_title,
			'sent_at'              => $this->sent_at?->format( 'Y-m-d H:i:s' ),
		);
	}
}
