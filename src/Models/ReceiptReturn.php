<?php
/**
 * Receipt Return Model.
 *
 * @package IHumbak\Invoices\Models
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Models;

/**
 * Receipt Return model.
 *
 * Used to document returns/refunds for receipts.
 * This is an informational document, not an official accounting document.
 */
class ReceiptReturn extends Document {

	/**
	 * Document type identifier.
	 */
	public const TYPE = 'receipt_return';

	/**
	 * Correction type: full correction (cancels entire receipt).
	 */
	public const CORRECTION_TYPE_FULL = 'full';

	/**
	 * Correction type: partial correction (corrects specific items).
	 */
	public const CORRECTION_TYPE_PARTIAL = 'partial';

	/**
	 * Correction reason.
	 *
	 * @var string
	 */
	private string $correction_reason = '';

	/**
	 * Correction type (full or partial).
	 *
	 * @var string
	 */
	private string $correction_type = self::CORRECTION_TYPE_PARTIAL;

	/**
	 * WooCommerce refund ID (optional).
	 *
	 * @var int|null
	 */
	private ?int $refund_id = null;

	/**
	 * Manual entry mode flag.
	 *
	 * When true, the receipt return references a receipt from an external system
	 * (not in the database) using original_document_number and original_document_date.
	 *
	 * @var bool
	 */
	private bool $is_manual_entry = false;

	/**
	 * Original document number (for manual entry mode).
	 *
	 * Stores the receipt number from an external system when is_manual_entry is true.
	 *
	 * @var string|null
	 */
	private ?string $original_document_number = null;

	/**
	 * Original document date (for manual entry mode).
	 *
	 * Stores the receipt date from an external system when is_manual_entry is true.
	 *
	 * @var \DateTimeImmutable|null
	 */
	private ?\DateTimeImmutable $original_document_date = null;

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
	 * Get correction reason.
	 *
	 * @return string
	 */
	public function getCorrectionReason(): string {
		return $this->correction_reason;
	}

	/**
	 * Set correction reason.
	 *
	 * @param string $reason Correction reason.
	 * @return self
	 */
	public function setCorrectionReason( string $reason ): self {
		$this->correction_reason = $reason;
		return $this;
	}

	/**
	 * Get correction type.
	 *
	 * @return string
	 */
	public function getCorrectionType(): string {
		return $this->correction_type;
	}

	/**
	 * Set correction type.
	 *
	 * @param string $type Correction type (full or partial).
	 * @return self
	 * @throws \InvalidArgumentException If correction type is not valid.
	 */
	public function setCorrectionType( string $type ): self {
		$valid_types = array( self::CORRECTION_TYPE_FULL, self::CORRECTION_TYPE_PARTIAL );

		if ( ! in_array( $type, $valid_types, true ) ) {
			throw new \InvalidArgumentException(
				sprintf(
					/* translators: 1: Invalid correction type, 2: List of valid types */
					esc_html__( 'Invalid correction type "%1$s". Valid types are: %2$s', 'ihumbak-invoices' ),
					esc_html( $type ),
					esc_html( implode( ', ', $valid_types ) )
				)
			);
		}

		$this->correction_type = $type;
		return $this;
	}

	/**
	 * Check if this is a full correction.
	 *
	 * @return bool
	 */
	public function isFullCorrection(): bool {
		return self::CORRECTION_TYPE_FULL === $this->correction_type;
	}

	/**
	 * Check if this is a partial correction.
	 *
	 * @return bool
	 */
	public function isPartialCorrection(): bool {
		return self::CORRECTION_TYPE_PARTIAL === $this->correction_type;
	}

	/**
	 * Get refund ID.
	 *
	 * @return int|null
	 */
	public function getRefundId(): ?int {
		return $this->refund_id;
	}

	/**
	 * Set refund ID.
	 *
	 * @param int|null $refund_id WooCommerce refund ID.
	 * @return self
	 */
	public function setRefundId( ?int $refund_id ): self {
		$this->refund_id = $refund_id;
		return $this;
	}

	/**
	 * Check if this receipt return is linked to a WooCommerce refund.
	 *
	 * @return bool
	 */
	public function hasRefund(): bool {
		return null !== $this->refund_id;
	}

	/**
	 * Check if this is a manual entry receipt return.
	 *
	 * @return bool
	 */
	public function isManualEntry(): bool {
		return $this->is_manual_entry;
	}

	/**
	 * Set manual entry mode.
	 *
	 * @param bool $is_manual_entry Manual entry flag.
	 * @return self
	 */
	public function setManualEntry( bool $is_manual_entry ): self {
		$this->is_manual_entry = $is_manual_entry;
		return $this;
	}

	/**
	 * Get original document number (for manual entry mode).
	 *
	 * @return string|null
	 */
	public function getOriginalDocumentNumber(): ?string {
		return $this->original_document_number;
	}

	/**
	 * Set original document number (for manual entry mode).
	 *
	 * @param string|null $number Original receipt number.
	 * @return self
	 */
	public function setOriginalDocumentNumber( ?string $number ): self {
		$this->original_document_number = $number;
		return $this;
	}

	/**
	 * Get original document date (for manual entry mode).
	 *
	 * @return \DateTimeImmutable|null
	 */
	public function getOriginalDocumentDate(): ?\DateTimeImmutable {
		return $this->original_document_date;
	}

	/**
	 * Set original document date (for manual entry mode).
	 *
	 * @param \DateTimeImmutable|null $date Original receipt date.
	 * @return self
	 */
	public function setOriginalDocumentDate( ?\DateTimeImmutable $date ): self {
		$this->original_document_date = $date;
		return $this;
	}

	/**
	 * Get the corrected document number for display purposes.
	 *
	 * Returns the original_document_number for manual entries,
	 * or null if this is a system-linked receipt return (caller should load the receipt).
	 *
	 * @return string|null The original document number for manual entries, null otherwise.
	 */
	public function getDisplayCorrectedDocumentNumber(): ?string {
		if ( $this->is_manual_entry ) {
			return $this->original_document_number;
		}
		return null;
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

		// Receipt return specific fields.
		if ( ! empty( $data['correction_reason'] ) ) {
			$receipt_return->setCorrectionReason( (string) $data['correction_reason'] );
		}
		if ( ! empty( $data['correction_type'] ) ) {
			try {
				$receipt_return->setCorrectionType( (string) $data['correction_type'] );
			} catch ( \InvalidArgumentException $e ) {
				// Use default type if invalid.
				$receipt_return->correction_type = self::CORRECTION_TYPE_PARTIAL;
			}
		}
		if ( isset( $data['refund_id'] ) && $data['refund_id'] ) {
			$receipt_return->setRefundId( (int) $data['refund_id'] );
		}

		// Manual entry fields.
		if ( isset( $data['is_manual_entry'] ) ) {
			$receipt_return->setManualEntry( (bool) $data['is_manual_entry'] );
		}
		if ( ! empty( $data['original_document_number'] ) ) {
			$receipt_return->setOriginalDocumentNumber( (string) $data['original_document_number'] );
		}
		if ( ! empty( $data['original_document_date'] ) ) {
			$date = $data['original_document_date'];
			if ( is_string( $date ) ) {
				$receipt_return->setOriginalDocumentDate( new \DateTimeImmutable( $date ) );
			} elseif ( $date instanceof \DateTimeImmutable ) {
				$receipt_return->setOriginalDocumentDate( $date );
			} elseif ( $date instanceof \DateTime ) {
				$receipt_return->setOriginalDocumentDate( \DateTimeImmutable::createFromMutable( $date ) );
			}
		}

		return $receipt_return;
	}

	/**
	 * Convert to array for database storage.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'id'                       => $this->id,
			'order_id'                 => $this->order_id,
			'document_type'            => $this->getDocumentType(),
			'document_number'          => $this->document_number,
			'issue_date'               => $this->issue_date?->format( 'Y-m-d' ),
			'sale_date'                => $this->sale_date?->format( 'Y-m-d' ),
			'due_date'                 => null, // Receipt returns don't have due date.
			'corrected_document_id'    => $this->corrected_document_id,
			'correction_reason'        => $this->correction_reason,
			'correction_type'          => $this->correction_type,
			'refund_id'                => $this->refund_id,
			'is_manual_entry'          => $this->is_manual_entry,
			'original_document_number' => $this->original_document_number,
			'original_document_date'   => $this->original_document_date?->format( 'Y-m-d' ),
			'buyer_data'               => $this->buyer?->toJson(),
			'seller_data'              => $this->seller?->toJson(),
			'subtotal'                 => $this->subtotal,
			'tax_total'                => $this->tax_total,
			'total'                    => $this->total,
			'currency'                 => $this->currency,
			'status'                   => $this->status,
			'pdf_path'                 => $this->pdf_path,
			'notes'                    => $this->notes,
			'sent_at'                  => $this->sent_at?->format( 'Y-m-d H:i:s' ),
			'items'                    => array_map(
				static fn( DocumentItem $item ): array => $item->toArray(),
				$this->items
			),
		);
	}
}
