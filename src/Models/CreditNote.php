<?php
/**
 * Credit Note Model.
 *
 * @package IHumbak\Invoices\Models
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Models;

/**
 * Credit Note (Correction) model.
 *
 * Used to correct previously issued invoices.
 */
class CreditNote extends Document {

	/**
	 * Document type identifier.
	 */
	public const TYPE = 'credit_note';

	/**
	 * Correction type: full correction (cancels entire invoice).
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
	 * Check if this credit note is linked to a WooCommerce refund.
	 *
	 * @return bool
	 */
	public function hasRefund(): bool {
		return null !== $this->refund_id;
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

		// Set base properties.
		if ( isset( $data['id'] ) ) {
			$credit_note->setId( (int) $data['id'] );
		}
		if ( isset( $data['order_id'] ) ) {
			$credit_note->setOrderId( (int) $data['order_id'] );
		}

		$credit_note->setDocumentNumber( (string) ( $data['document_number'] ?? '' ) );

		// Dates - safely parse with error handling.
		if ( ! empty( $data['issue_date'] ) ) {
			$date = self::parseDate( (string) $data['issue_date'] );
			if ( $date ) {
				$credit_note->setIssueDate( $date );
			}
		}
		if ( ! empty( $data['sale_date'] ) ) {
			$date = self::parseDate( (string) $data['sale_date'] );
			if ( $date ) {
				$credit_note->setSaleDate( $date );
			}
		}

		// Corrected document ID (required for credit notes).
		if ( isset( $data['corrected_document_id'] ) ) {
			$credit_note->setCorrectedDocumentId( (int) $data['corrected_document_id'] );
		}

		// Credit note specific fields.
		if ( ! empty( $data['correction_reason'] ) ) {
			$credit_note->setCorrectionReason( (string) $data['correction_reason'] );
		}
		if ( ! empty( $data['correction_type'] ) ) {
			try {
				$credit_note->setCorrectionType( (string) $data['correction_type'] );
			} catch ( \InvalidArgumentException $e ) {
				// Use default type if invalid.
				$credit_note->correction_type = self::CORRECTION_TYPE_PARTIAL;
			}
		}
		if ( isset( $data['refund_id'] ) && $data['refund_id'] ) {
			$credit_note->setRefundId( (int) $data['refund_id'] );
		}

		// Buyer/Seller.
		if ( isset( $data['buyer_data'] ) ) {
			$buyer_data = is_string( $data['buyer_data'] )
				? json_decode( $data['buyer_data'], true )
				: $data['buyer_data'];
			if ( is_array( $buyer_data ) ) {
				$credit_note->setBuyer( Buyer::fromArray( $buyer_data ) );
			}
		}
		if ( isset( $data['seller_data'] ) ) {
			$seller_data = is_string( $data['seller_data'] )
				? json_decode( $data['seller_data'], true )
				: $data['seller_data'];
			if ( is_array( $seller_data ) ) {
				$credit_note->setSeller( Seller::fromArray( $seller_data ) );
			}
		}

		// Totals (can be negative for credit notes).
		$credit_note->setSubtotal( (float) ( $data['subtotal'] ?? 0.0 ) );
		$credit_note->setTaxTotal( (float) ( $data['tax_total'] ?? 0.0 ) );
		$credit_note->setTotal( (float) ( $data['total'] ?? 0.0 ) );
		$credit_note->setCurrency( (string) ( $data['currency'] ?? 'PLN' ) );

		// Status and other.
		$credit_note->setStatus( (string) ( $data['status'] ?? self::STATUS_DRAFT ) );
		$credit_note->setPdfPath( $data['pdf_path'] ?? null );
		$credit_note->setNotes( (string) ( $data['notes'] ?? '' ) );

		// Timestamps - safely parse with error handling.
		if ( ! empty( $data['created_at'] ) ) {
			$date = self::parseDate( (string) $data['created_at'] );
			if ( $date ) {
				$credit_note->setCreatedAt( $date );
			}
		}
		if ( ! empty( $data['updated_at'] ) ) {
			$date = self::parseDate( (string) $data['updated_at'] );
			if ( $date ) {
				$credit_note->setUpdatedAt( $date );
			}
		}

		// Items.
		if ( isset( $data['items'] ) && is_array( $data['items'] ) ) {
			foreach ( $data['items'] as $item_data ) {
				if ( is_array( $item_data ) ) {
					$credit_note->addItem( DocumentItem::fromArray( $item_data ) );
				}
			}
		}

		return $credit_note;
	}

	/**
	 * Convert to array for database storage.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'id'                    => $this->id,
			'order_id'              => $this->order_id,
			'document_type'         => $this->getDocumentType(),
			'document_number'       => $this->document_number,
			'issue_date'            => $this->issue_date?->format( 'Y-m-d' ),
			'sale_date'             => $this->sale_date?->format( 'Y-m-d' ),
			'due_date'              => null, // Credit notes don't have due date.
			'corrected_document_id' => $this->corrected_document_id,
			'correction_reason'     => $this->correction_reason,
			'correction_type'       => $this->correction_type,
			'refund_id'             => $this->refund_id,
			'buyer_data'            => $this->buyer?->toJson(),
			'seller_data'           => $this->seller?->toJson(),
			'subtotal'              => $this->subtotal,
			'tax_total'             => $this->tax_total,
			'total'                 => $this->total,
			'currency'              => $this->currency,
			'status'                => $this->status,
			'pdf_path'              => $this->pdf_path,
			'notes'                 => $this->notes,
			'items'                 => array_map(
				static fn( DocumentItem $item ): array => $item->toArray(),
				$this->items
			),
		);
	}
}
