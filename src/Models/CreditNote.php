<?php
/**
 * Credit Note Model.
 *
 * @package IHumbak\Invoices\Models
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Models;

use IHumbak\Invoices\Models\Traits\HasCorrectionFields;

/**
 * Credit Note (Correction) model.
 *
 * Used to correct previously issued invoices.
 */
class CreditNote extends Document {

	use HasCorrectionFields;

	/**
	 * Document type identifier.
	 */
	public const TYPE = 'credit_note';

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
		return __( 'Credit Note', 'ihumbak-invoices' );
	}

	/**
	 * Get available correction types.
	 *
	 * @return array<string, string>
	 */
	public static function getCorrectionTypes(): array {
		return array(
			self::CORRECTION_TYPE_PARTIAL => __( 'Partial Correction', 'ihumbak-invoices' ),
			self::CORRECTION_TYPE_FULL    => __( 'Full Correction', 'ihumbak-invoices' ),
		);
	}

	/**
	 * Create from array.
	 *
	 * @param array<string, mixed> $data Credit Note data.
	 * @return self
	 */
	public static function fromArray( array $data ): self {
		$credit_note = new self();

		// Hydrate common document properties.
		$credit_note->hydrateFromArray( $data );

		// Hydrate correction-specific fields from trait.
		$credit_note->hydrateCorrectionFields( $data );

		return $credit_note;
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
			'due_date'              => null, // Credit notes don't have due date.
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
