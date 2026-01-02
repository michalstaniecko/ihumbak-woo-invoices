<?php
/**
 * Has Correction Fields Trait.
 *
 * Provides common fields and methods for correction documents (CreditNote, ReceiptReturn).
 *
 * @package IHumbak\Invoices\Models\Traits
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Models\Traits;

/**
 * Trait for documents that correct other documents.
 *
 * Used by CreditNote and ReceiptReturn models.
 *
 * Note: Classes using this trait must define these constants:
 * - CORRECTION_TYPE_FULL = 'full'
 * - CORRECTION_TYPE_PARTIAL = 'partial'
 */
trait HasCorrectionFields {

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
	private string $correction_type = 'partial';

	/**
	 * WooCommerce refund ID (optional).
	 *
	 * @var int|null
	 */
	private ?int $refund_id = null;

	/**
	 * Manual entry mode flag.
	 *
	 * When true, the document references a source document from an external system
	 * (not in the database) using original_document_number and original_document_date.
	 *
	 * @var bool
	 */
	private bool $is_manual_entry = false;

	/**
	 * Original document number (for manual entry mode).
	 *
	 * Stores the document number from an external system when is_manual_entry is true.
	 *
	 * @var string|null
	 */
	private ?string $original_document_number = null;

	/**
	 * Original document date (for manual entry mode).
	 *
	 * Stores the document date from an external system when is_manual_entry is true.
	 *
	 * @var \DateTimeImmutable|null
	 */
	private ?\DateTimeImmutable $original_document_date = null;

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
		$valid_types = array( 'full', 'partial' );

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
		return 'full' === $this->correction_type;
	}

	/**
	 * Check if this is a partial correction.
	 *
	 * @return bool
	 */
	public function isPartialCorrection(): bool {
		return 'partial' === $this->correction_type;
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
	 * Check if this document is linked to a WooCommerce refund.
	 *
	 * @return bool
	 */
	public function hasRefund(): bool {
		return null !== $this->refund_id;
	}

	/**
	 * Check if this is a manual entry document.
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
	 * @param string|null $number Original document number.
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
	 * @param \DateTimeImmutable|null $date Original document date.
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
	 * or null if this is a system-linked document (caller should load the source document).
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
	abstract public static function getCorrectionTypes(): array;

	/**
	 * Hydrate correction fields from array data.
	 *
	 * @param array<string, mixed> $data Data array.
	 * @return void
	 */
	protected function hydrateCorrectionFields( array $data ): void {
		if ( ! empty( $data['correction_reason'] ) ) {
			$this->setCorrectionReason( (string) $data['correction_reason'] );
		}

		if ( ! empty( $data['correction_type'] ) ) {
			try {
				$this->setCorrectionType( (string) $data['correction_type'] );
			} catch ( \InvalidArgumentException $e ) {
				// Use default type if invalid.
				$this->correction_type = 'partial';
			}
		}

		if ( isset( $data['refund_id'] ) && $data['refund_id'] ) {
			$this->setRefundId( (int) $data['refund_id'] );
		}

		if ( isset( $data['is_manual_entry'] ) ) {
			$this->setManualEntry( (bool) $data['is_manual_entry'] );
		}

		if ( ! empty( $data['original_document_number'] ) ) {
			$this->setOriginalDocumentNumber( (string) $data['original_document_number'] );
		}

		if ( ! empty( $data['original_document_date'] ) ) {
			$date = $data['original_document_date'];
			if ( is_string( $date ) ) {
				$this->setOriginalDocumentDate( new \DateTimeImmutable( $date ) );
			} elseif ( $date instanceof \DateTimeImmutable ) {
				$this->setOriginalDocumentDate( $date );
			} elseif ( $date instanceof \DateTime ) {
				$this->setOriginalDocumentDate( \DateTimeImmutable::createFromMutable( $date ) );
			}
		}
	}

	/**
	 * Get correction fields as array for database storage.
	 *
	 * @return array<string, mixed>
	 */
	protected function getCorrectionFieldsArray(): array {
		return array(
			'correction_reason'        => $this->correction_reason,
			'correction_type'          => $this->correction_type,
			'refund_id'                => $this->refund_id,
			'is_manual_entry'          => $this->is_manual_entry,
			'original_document_number' => $this->original_document_number,
			'original_document_date'   => $this->original_document_date?->format( 'Y-m-d' ),
		);
	}
}
