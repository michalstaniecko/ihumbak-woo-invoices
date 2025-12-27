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

		// Set base properties.
		if ( isset( $data['id'] ) ) {
			$receipt->setId( (int) $data['id'] );
		}
		if ( isset( $data['order_id'] ) ) {
			$receipt->setOrderId( (int) $data['order_id'] );
		}

		$receipt->setDocumentNumber( (string) ( $data['document_number'] ?? '' ) );

		// Dates - safely parse with error handling.
		if ( ! empty( $data['issue_date'] ) ) {
			$date = self::parseDate( (string) $data['issue_date'] );
			if ( $date ) {
				$receipt->setIssueDate( $date );
			}
		}
		if ( ! empty( $data['sale_date'] ) ) {
			$date = self::parseDate( (string) $data['sale_date'] );
			if ( $date ) {
				$receipt->setSaleDate( $date );
			}
		}
		if ( ! empty( $data['payment_date'] ) ) {
			$date = self::parseDate( (string) $data['payment_date'] );
			if ( $date ) {
				$receipt->setPaymentDate( $date );
			}
		}

		// Buyer/Seller.
		if ( isset( $data['buyer_data'] ) ) {
			$buyer_data = is_string( $data['buyer_data'] )
				? json_decode( $data['buyer_data'], true )
				: $data['buyer_data'];
			if ( is_array( $buyer_data ) ) {
				$receipt->setBuyer( Buyer::fromArray( $buyer_data ) );
			}
		}
		if ( isset( $data['seller_data'] ) ) {
			$seller_data = is_string( $data['seller_data'] )
				? json_decode( $data['seller_data'], true )
				: $data['seller_data'];
			if ( is_array( $seller_data ) ) {
				$receipt->setSeller( Seller::fromArray( $seller_data ) );
			}
		}

		// Totals.
		$receipt->setSubtotal( (float) ( $data['subtotal'] ?? 0.0 ) );
		$receipt->setTaxTotal( (float) ( $data['tax_total'] ?? 0.0 ) );
		$receipt->setTotal( (float) ( $data['total'] ?? 0.0 ) );
		$receipt->setCurrency( (string) ( $data['currency'] ?? 'PLN' ) );

		// Status and other.
		$receipt->setStatus( (string) ( $data['status'] ?? self::STATUS_DRAFT ) );
		$receipt->setPdfPath( $data['pdf_path'] ?? null );
		$receipt->setNotes( (string) ( $data['notes'] ?? '' ) );
		$receipt->setPaymentMethod( (string) ( $data['payment_method'] ?? '' ) );
		$receipt->setPaymentMethodId( (string) ( $data['payment_method_id'] ?? '' ) );
		$receipt->setPaymentMethodTitle( (string) ( $data['payment_method_title'] ?? '' ) );

		// Timestamps - safely parse with error handling.
		if ( ! empty( $data['created_at'] ) ) {
			$date = self::parseDate( (string) $data['created_at'] );
			if ( $date ) {
				$receipt->setCreatedAt( $date );
			}
		}
		if ( ! empty( $data['updated_at'] ) ) {
			$date = self::parseDate( (string) $data['updated_at'] );
			if ( $date ) {
				$receipt->setUpdatedAt( $date );
			}
		}

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
		);
	}
}
