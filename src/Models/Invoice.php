<?php
/**
 * Invoice Model.
 *
 * @package IHumbak\Invoices\Models
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Models;

/**
 * VAT Invoice model.
 */
class Invoice extends Document {

	/**
	 * Document type identifier.
	 */
	public const TYPE = 'invoice';

	/**
	 * Payment method.
	 *
	 * @var string
	 */
	private string $payment_method = '';

	/**
	 * Default payment term in days.
	 */
	public const DEFAULT_PAYMENT_TERM = 14;

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
		return __( 'VAT Invoice', 'ihumbak-invoices' );
	}

	/**
	 * Get payment method.
	 *
	 * @return string
	 */
	public function getPaymentMethod(): string {
		return $this->payment_method;
	}

	/**
	 * Set payment method.
	 *
	 * @param string $method Payment method.
	 * @return self
	 */
	public function setPaymentMethod( string $method ): self {
		$this->payment_method = $method;
		return $this;
	}

	/**
	 * Get available payment methods.
	 *
	 * @return array<string, string>
	 */
	public static function getPaymentMethods(): array {
		return array(
			'transfer' => __( 'Bank transfer', 'ihumbak-invoices' ),
			'cash'     => __( 'Cash', 'ihumbak-invoices' ),
			'card'     => __( 'Credit/Debit card', 'ihumbak-invoices' ),
			'online'   => __( 'Online payment', 'ihumbak-invoices' ),
		);
	}

	/**
	 * Safely parse date string to DateTimeImmutable.
	 *
	 * @param string $date_string Date string to parse.
	 * @return \DateTimeImmutable|null Parsed date or null on failure.
	 */
	private static function parseDate( string $date_string ): ?\DateTimeImmutable {
		try {
			return new \DateTimeImmutable( $date_string );
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * Create from array.
	 *
	 * @param array<string, mixed> $data Invoice data.
	 * @return self
	 */
	public static function fromArray( array $data ): self {
		$invoice = new self();

		// Set base properties.
		if ( isset( $data['id'] ) ) {
			$invoice->setId( (int) $data['id'] );
		}
		if ( isset( $data['order_id'] ) ) {
			$invoice->setOrderId( (int) $data['order_id'] );
		}

		$invoice->setDocumentNumber( (string) ( $data['document_number'] ?? '' ) );

		// Dates - safely parse with error handling.
		if ( ! empty( $data['issue_date'] ) ) {
			$date = self::parseDate( (string) $data['issue_date'] );
			if ( $date ) {
				$invoice->setIssueDate( $date );
			}
		}
		if ( ! empty( $data['sale_date'] ) ) {
			$date = self::parseDate( (string) $data['sale_date'] );
			if ( $date ) {
				$invoice->setSaleDate( $date );
			}
		}
		if ( ! empty( $data['due_date'] ) ) {
			$date = self::parseDate( (string) $data['due_date'] );
			if ( $date ) {
				$invoice->setDueDate( $date );
			}
		}

		// Buyer/Seller.
		if ( isset( $data['buyer_data'] ) ) {
			$buyer_data = is_string( $data['buyer_data'] )
				? json_decode( $data['buyer_data'], true )
				: $data['buyer_data'];
			if ( is_array( $buyer_data ) ) {
				$invoice->setBuyer( Buyer::fromArray( $buyer_data ) );
			}
		}
		if ( isset( $data['seller_data'] ) ) {
			$seller_data = is_string( $data['seller_data'] )
				? json_decode( $data['seller_data'], true )
				: $data['seller_data'];
			if ( is_array( $seller_data ) ) {
				$invoice->setSeller( Seller::fromArray( $seller_data ) );
			}
		}

		// Totals.
		$invoice->setSubtotal( (float) ( $data['subtotal'] ?? 0.0 ) );
		$invoice->setTaxTotal( (float) ( $data['tax_total'] ?? 0.0 ) );
		$invoice->setTotal( (float) ( $data['total'] ?? 0.0 ) );
		$invoice->setCurrency( (string) ( $data['currency'] ?? 'PLN' ) );

		// Status and other.
		$invoice->setStatus( (string) ( $data['status'] ?? self::STATUS_DRAFT ) );
		$invoice->setPdfPath( $data['pdf_path'] ?? null );
		$invoice->setNotes( (string) ( $data['notes'] ?? '' ) );
		$invoice->setPaymentMethod( (string) ( $data['payment_method'] ?? '' ) );

		// Timestamps - safely parse with error handling.
		if ( ! empty( $data['created_at'] ) ) {
			$date = self::parseDate( (string) $data['created_at'] );
			if ( $date ) {
				$invoice->setCreatedAt( $date );
			}
		}
		if ( ! empty( $data['updated_at'] ) ) {
			$date = self::parseDate( (string) $data['updated_at'] );
			if ( $date ) {
				$invoice->setUpdatedAt( $date );
			}
		}

		return $invoice;
	}

	/**
	 * Convert to array for database storage.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'id'              => $this->id,
			'order_id'        => $this->order_id,
			'document_type'   => $this->getDocumentType(),
			'document_number' => $this->document_number,
			'issue_date'      => $this->issue_date?->format( 'Y-m-d' ),
			'sale_date'       => $this->sale_date?->format( 'Y-m-d' ),
			'due_date'        => $this->due_date?->format( 'Y-m-d' ),
			'buyer_data'      => $this->buyer?->toJson(),
			'seller_data'     => $this->seller?->toJson(),
			'subtotal'        => $this->subtotal,
			'tax_total'       => $this->tax_total,
			'total'           => $this->total,
			'currency'        => $this->currency,
			'status'          => $this->status,
			'pdf_path'        => $this->pdf_path,
			'notes'           => $this->notes,
			'payment_method'  => $this->payment_method,
		);
	}
}
