<?php
/**
 * Receipt Return Model.
 *
 * @package IHumbak\Invoices\Models
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Models;

use IHumbak\Invoices\Models\Traits\HasCorrectionFields;

/**
 * Receipt Return model.
 *
 * Used to document returns/refunds for receipts.
 * This is an informational document, not an official accounting document.
 */
class ReceiptReturn extends Document {

	use HasCorrectionFields;

	/**
	 * Document type identifier.
	 */
	public const TYPE = 'receipt_return';

	/**
	 * Correction type: full correction (cancels entire document).
	 */
	public const CORRECTION_TYPE_FULL = 'full';

	/**
	 * Correction type: partial correction (corrects specific items).
	 */
	public const CORRECTION_TYPE_PARTIAL = 'partial';

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
		return __( 'Receipt Return', 'ihumbak-invoices' );
	}

	/**
	 * Get available correction types.
	 *
	 * @return array<string, string>
	 */
	public static function getCorrectionTypes(): array {
		return array(
			self::CORRECTION_TYPE_PARTIAL => __( 'Partial Return', 'ihumbak-invoices' ),
			self::CORRECTION_TYPE_FULL    => __( 'Full Return', 'ihumbak-invoices' ),
		);
	}

	/**
	 * Create from array.
	 *
	 * @param array<string, mixed> $data Receipt Return data.
	 * @return self
	 */
	public static function fromArray( array $data ): self {
		$receipt_return = new self();

		// Hydrate common document properties.
		$receipt_return->hydrateFromArray( $data );

		// Hydrate correction-specific fields from trait.
		$receipt_return->hydrateCorrectionFields( $data );

		return $receipt_return;
	}

	/**
	 * Convert to array for database storage.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		$base = array(
			'id'                    => $this->id,
			'order_id'              => $this->order_id,
			'document_type'         => $this->getDocumentType(),
			'document_number'       => $this->document_number,
			'issue_date'            => $this->issue_date?->format( 'Y-m-d' ),
			'sale_date'             => $this->sale_date?->format( 'Y-m-d' ),
			'due_date'              => null, // Receipt returns don't have due date.
			'corrected_document_id' => $this->corrected_document_id,
			'buyer_data'            => $this->buyer?->toJson(),
			'seller_data'           => $this->seller?->toJson(),
			'subtotal'              => $this->subtotal,
			'tax_total'             => $this->tax_total,
			'total'                 => $this->total,
			'currency'              => $this->currency,
			'status'                => $this->status,
			'pdf_path'              => $this->pdf_path,
			'notes'                 => $this->notes,
			'sent_at'               => $this->sent_at?->format( 'Y-m-d H:i:s' ),
			'items'                 => array_map(
				static fn( DocumentItem $item ): array => $item->toArray(),
				$this->items
			),
		);

		// Merge correction fields from trait.
		return array_merge( $base, $this->getCorrectionFieldsArray() );
	}
}
