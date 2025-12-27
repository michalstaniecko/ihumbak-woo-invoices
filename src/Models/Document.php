<?php
/**
 * Abstract Document Model.
 *
 * @package IHumbak\Invoices\Models
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Models;

use DateTimeImmutable;

/**
 * Abstract base class for all document types.
 */
abstract class Document {

	/**
	 * Document statuses.
	 */
	public const STATUS_DRAFT     = 'draft';
	public const STATUS_ISSUED    = 'issued';
	public const STATUS_SENT      = 'sent';
	public const STATUS_PAID      = 'paid';
	public const STATUS_CANCELLED = 'cancelled';

	/**
	 * Document ID.
	 *
	 * @var int|null
	 */
	protected ?int $id = null;

	/**
	 * WooCommerce Order ID.
	 *
	 * @var int|null
	 */
	protected ?int $order_id = null;

	/**
	 * Document number.
	 *
	 * @var string
	 */
	protected string $document_number = '';

	/**
	 * Issue date.
	 *
	 * @var DateTimeImmutable|null
	 */
	protected ?DateTimeImmutable $issue_date = null;

	/**
	 * Sale date.
	 *
	 * @var DateTimeImmutable|null
	 */
	protected ?DateTimeImmutable $sale_date = null;

	/**
	 * Due date (for invoices).
	 *
	 * @var DateTimeImmutable|null
	 */
	protected ?DateTimeImmutable $due_date = null;

	/**
	 * Payment date (date when order was paid).
	 *
	 * @var DateTimeImmutable|null
	 */
	protected ?DateTimeImmutable $payment_date = null;

	/**
	 * Corrected document ID (for corrections).
	 *
	 * @var int|null
	 */
	protected ?int $corrected_document_id = null;

	/**
	 * Buyer data.
	 *
	 * @var Buyer|null
	 */
	protected ?Buyer $buyer = null;

	/**
	 * Seller data.
	 *
	 * @var Seller|null
	 */
	protected ?Seller $seller = null;

	/**
	 * Document items.
	 *
	 * @var DocumentItem[]
	 */
	protected array $items = array();

	/**
	 * Subtotal (net).
	 *
	 * @var float
	 */
	protected float $subtotal = 0.0;

	/**
	 * Tax total.
	 *
	 * @var float
	 */
	protected float $tax_total = 0.0;

	/**
	 * Total (gross).
	 *
	 * @var float
	 */
	protected float $total = 0.0;

	/**
	 * Currency code.
	 *
	 * @var string
	 */
	protected string $currency = 'PLN';

	/**
	 * Document status.
	 *
	 * @var string
	 */
	protected string $status = self::STATUS_DRAFT;

	/**
	 * PDF file path.
	 *
	 * @var string|null
	 */
	protected ?string $pdf_path = null;

	/**
	 * Notes.
	 *
	 * @var string
	 */
	protected string $notes = '';

	/**
	 * Payment method type (transfer, cash, card, online).
	 *
	 * @var string
	 */
	protected string $payment_method = '';

	/**
	 * Payment method ID from WooCommerce (bacs, przelewy24, etc.).
	 *
	 * @var string
	 */
	protected string $payment_method_id = '';

	/**
	 * Payment method title from WooCommerce (human readable name).
	 *
	 * @var string
	 */
	protected string $payment_method_title = '';

	/**
	 * Created at timestamp.
	 *
	 * @var DateTimeImmutable|null
	 */
	protected ?DateTimeImmutable $created_at = null;

	/**
	 * Updated at timestamp.
	 *
	 * @var DateTimeImmutable|null
	 */
	protected ?DateTimeImmutable $updated_at = null;

	/**
	 * Get document type.
	 *
	 * @return string
	 */
	abstract public function getDocumentType(): string;

	/**
	 * Get document type label.
	 *
	 * @return string
	 */
	abstract public function getDocumentTypeLabel(): string;

	/**
	 * Get ID.
	 *
	 * @return int|null
	 */
	public function getId(): ?int {
		return $this->id;
	}

	/**
	 * Set ID.
	 *
	 * @param int|null $id Document ID.
	 * @return self
	 */
	public function setId( ?int $id ): self {
		$this->id = $id;
		return $this;
	}

	/**
	 * Get order ID.
	 *
	 * @return int|null
	 */
	public function getOrderId(): ?int {
		return $this->order_id;
	}

	/**
	 * Set order ID.
	 *
	 * @param int|null $order_id Order ID.
	 * @return self
	 */
	public function setOrderId( ?int $order_id ): self {
		$this->order_id = $order_id;
		return $this;
	}

	/**
	 * Get document number.
	 *
	 * @return string
	 */
	public function getDocumentNumber(): string {
		return $this->document_number;
	}

	/**
	 * Set document number.
	 *
	 * @param string $number Document number.
	 * @return self
	 */
	public function setDocumentNumber( string $number ): self {
		$this->document_number = $number;
		return $this;
	}

	/**
	 * Get issue date.
	 *
	 * @return DateTimeImmutable|null
	 */
	public function getIssueDate(): ?DateTimeImmutable {
		return $this->issue_date;
	}

	/**
	 * Set issue date.
	 *
	 * @param DateTimeImmutable|null $date Issue date.
	 * @return self
	 */
	public function setIssueDate( ?DateTimeImmutable $date ): self {
		$this->issue_date = $date;
		return $this;
	}

	/**
	 * Get sale date.
	 *
	 * @return DateTimeImmutable|null
	 */
	public function getSaleDate(): ?DateTimeImmutable {
		return $this->sale_date;
	}

	/**
	 * Set sale date.
	 *
	 * @param DateTimeImmutable|null $date Sale date.
	 * @return self
	 */
	public function setSaleDate( ?DateTimeImmutable $date ): self {
		$this->sale_date = $date;
		return $this;
	}

	/**
	 * Get due date.
	 *
	 * @return DateTimeImmutable|null
	 */
	public function getDueDate(): ?DateTimeImmutable {
		return $this->due_date;
	}

	/**
	 * Set due date.
	 *
	 * @param DateTimeImmutable|null $date Due date.
	 * @return self
	 */
	public function setDueDate( ?DateTimeImmutable $date ): self {
		$this->due_date = $date;
		return $this;
	}

	/**
	 * Get payment date.
	 *
	 * @return DateTimeImmutable|null
	 */
	public function getPaymentDate(): ?DateTimeImmutable {
		return $this->payment_date;
	}

	/**
	 * Set payment date.
	 *
	 * @param DateTimeImmutable|null $date Payment date.
	 * @return self
	 */
	public function setPaymentDate( ?DateTimeImmutable $date ): self {
		$this->payment_date = $date;
		return $this;
	}

	/**
	 * Check if document is paid (has payment date).
	 *
	 * @return bool
	 */
	public function isPaid(): bool {
		return null !== $this->payment_date;
	}

	/**
	 * Get corrected document ID.
	 *
	 * @return int|null
	 */
	public function getCorrectedDocumentId(): ?int {
		return $this->corrected_document_id;
	}

	/**
	 * Set corrected document ID.
	 *
	 * @param int|null $id Corrected document ID.
	 * @return self
	 */
	public function setCorrectedDocumentId( ?int $id ): self {
		$this->corrected_document_id = $id;
		return $this;
	}

	/**
	 * Check if this document is a correction.
	 *
	 * @return bool
	 */
	public function isCorrection(): bool {
		return null !== $this->corrected_document_id;
	}

	/**
	 * Get buyer.
	 *
	 * @return Buyer|null
	 */
	public function getBuyer(): ?Buyer {
		return $this->buyer;
	}

	/**
	 * Set buyer.
	 *
	 * @param Buyer|null $buyer Buyer data.
	 * @return self
	 */
	public function setBuyer( ?Buyer $buyer ): self {
		$this->buyer = $buyer;
		return $this;
	}

	/**
	 * Get seller.
	 *
	 * @return Seller|null
	 */
	public function getSeller(): ?Seller {
		return $this->seller;
	}

	/**
	 * Set seller.
	 *
	 * @param Seller|null $seller Seller data.
	 * @return self
	 */
	public function setSeller( ?Seller $seller ): self {
		$this->seller = $seller;
		return $this;
	}

	/**
	 * Get items.
	 *
	 * @return DocumentItem[]
	 */
	public function getItems(): array {
		return $this->items;
	}

	/**
	 * Set items.
	 *
	 * @param DocumentItem[] $items Document items.
	 * @return self
	 */
	public function setItems( array $items ): self {
		$this->items = $items;
		return $this;
	}

	/**
	 * Add item.
	 *
	 * @param DocumentItem $item Document item.
	 * @return self
	 */
	public function addItem( DocumentItem $item ): self {
		$this->items[] = $item;
		return $this;
	}

	/**
	 * Get subtotal (net).
	 *
	 * @return float
	 */
	public function getSubtotal(): float {
		return $this->subtotal;
	}

	/**
	 * Set subtotal (net).
	 *
	 * @param float $subtotal Subtotal.
	 * @return self
	 */
	public function setSubtotal( float $subtotal ): self {
		$this->subtotal = $subtotal;
		return $this;
	}

	/**
	 * Get tax total.
	 *
	 * @return float
	 */
	public function getTaxTotal(): float {
		return $this->tax_total;
	}

	/**
	 * Set tax total.
	 *
	 * @param float $tax_total Tax total.
	 * @return self
	 */
	public function setTaxTotal( float $tax_total ): self {
		$this->tax_total = $tax_total;
		return $this;
	}

	/**
	 * Get total (gross).
	 *
	 * @return float
	 */
	public function getTotal(): float {
		return $this->total;
	}

	/**
	 * Set total (gross).
	 *
	 * @param float $total Total.
	 * @return self
	 */
	public function setTotal( float $total ): self {
		$this->total = $total;
		return $this;
	}

	/**
	 * Get currency.
	 *
	 * @return string
	 */
	public function getCurrency(): string {
		return $this->currency;
	}

	/**
	 * Set currency.
	 *
	 * @param string $currency Currency code.
	 * @return self
	 */
	public function setCurrency( string $currency ): self {
		$this->currency = $currency;
		return $this;
	}

	/**
	 * Get status.
	 *
	 * @return string
	 */
	public function getStatus(): string {
		return $this->status;
	}

	/**
	 * Set status.
	 *
	 * @param string $status Document status.
	 * @return self
	 * @throws \InvalidArgumentException If status is not valid.
	 */
	public function setStatus( string $status ): self {
		$valid_statuses = array(
			self::STATUS_DRAFT,
			self::STATUS_ISSUED,
			self::STATUS_SENT,
			self::STATUS_PAID,
			self::STATUS_CANCELLED,
		);

		if ( ! in_array( $status, $valid_statuses, true ) ) {
			throw new \InvalidArgumentException(
				sprintf(
					/* translators: 1: Invalid status value, 2: List of valid statuses */
					esc_html__( 'Invalid document status "%1$s". Valid statuses are: %2$s', 'ihumbak-invoices' ),
					esc_html( $status ),
					esc_html( implode( ', ', $valid_statuses ) )
				)
			);
		}

		$this->status = $status;
		return $this;
	}

	/**
	 * Get PDF path.
	 *
	 * @return string|null
	 */
	public function getPdfPath(): ?string {
		return $this->pdf_path;
	}

	/**
	 * Set PDF path.
	 *
	 * @param string|null $path PDF file path.
	 * @return self
	 */
	public function setPdfPath( ?string $path ): self {
		$this->pdf_path = $path;
		return $this;
	}

	/**
	 * Get notes.
	 *
	 * @return string
	 */
	public function getNotes(): string {
		return $this->notes;
	}

	/**
	 * Set notes.
	 *
	 * @param string $notes Notes.
	 * @return self
	 */
	public function setNotes( string $notes ): self {
		$this->notes = $notes;
		return $this;
	}

	/**
	 * Get payment method type.
	 *
	 * @return string
	 */
	public function getPaymentMethod(): string {
		return $this->payment_method;
	}

	/**
	 * Set payment method type.
	 *
	 * @param string $method Payment method type.
	 * @return self
	 * @throws \InvalidArgumentException If method is not valid (unless empty).
	 */
	public function setPaymentMethod( string $method ): self {
		if ( '' !== $method && ! array_key_exists( $method, self::getPaymentMethods() ) ) {
			throw new \InvalidArgumentException(
				sprintf(
					/* translators: 1: Invalid payment method value */
					esc_html__( 'Invalid payment method: %s', 'ihumbak-invoices' ),
					esc_html( $method )
				)
			);
		}
		$this->payment_method = $method;
		return $this;
	}

	/**
	 * Get payment method ID.
	 *
	 * @return string
	 */
	public function getPaymentMethodId(): string {
		return $this->payment_method_id;
	}

	/**
	 * Set payment method ID.
	 *
	 * @param string $id Payment method ID.
	 * @return self
	 */
	public function setPaymentMethodId( string $id ): self {
		$this->payment_method_id = $id;
		return $this;
	}

	/**
	 * Get payment method title.
	 *
	 * @return string
	 */
	public function getPaymentMethodTitle(): string {
		return $this->payment_method_title;
	}

	/**
	 * Set payment method title.
	 *
	 * @param string $title Payment method title.
	 * @return self
	 */
	public function setPaymentMethodTitle( string $title ): self {
		$this->payment_method_title = $title;
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
			'card'     => __( 'Card payment', 'ihumbak-invoices' ),
			'online'   => __( 'Online payment', 'ihumbak-invoices' ),
		);
	}

	/**
	 * Get human-readable payment method name.
	 *
	 * Returns payment_method_title if available, otherwise falls back to
	 * the label for payment_method type.
	 *
	 * @return string Payment method display name, or empty string if not set.
	 */
	public function getPaymentMethodDisplayName(): string {
		// First try the specific title from WooCommerce.
		if ( '' !== $this->payment_method_title ) {
			return $this->payment_method_title;
		}

		// Fall back to type label.
		if ( '' !== $this->payment_method ) {
			$methods = self::getPaymentMethods();
			return $methods[ $this->payment_method ] ?? $this->payment_method;
		}

		return '';
	}

	/**
	 * Get created at.
	 *
	 * @return DateTimeImmutable|null
	 */
	public function getCreatedAt(): ?DateTimeImmutable {
		return $this->created_at;
	}

	/**
	 * Set created at.
	 *
	 * @param DateTimeImmutable|null $date Created at timestamp.
	 * @return self
	 */
	public function setCreatedAt( ?DateTimeImmutable $date ): self {
		$this->created_at = $date;
		return $this;
	}

	/**
	 * Get updated at.
	 *
	 * @return DateTimeImmutable|null
	 */
	public function getUpdatedAt(): ?DateTimeImmutable {
		return $this->updated_at;
	}

	/**
	 * Set updated at.
	 *
	 * @param DateTimeImmutable|null $date Updated at timestamp.
	 * @return self
	 */
	public function setUpdatedAt( ?DateTimeImmutable $date ): self {
		$this->updated_at = $date;
		return $this;
	}

	/**
	 * Check if document is draft.
	 *
	 * @return bool
	 */
	public function isDraft(): bool {
		return self::STATUS_DRAFT === $this->status;
	}

	/**
	 * Check if document is issued.
	 *
	 * @return bool
	 */
	public function isIssued(): bool {
		return self::STATUS_ISSUED === $this->status;
	}

	/**
	 * Check if document is cancelled.
	 *
	 * @return bool
	 */
	public function isCancelled(): bool {
		return self::STATUS_CANCELLED === $this->status;
	}

	/**
	 * Check if document can be edited.
	 *
	 * @return bool
	 */
	public function canBeEdited(): bool {
		return $this->isDraft();
	}

	/**
	 * Get available statuses.
	 *
	 * @return array<string, string>
	 */
	public static function getStatuses(): array {
		return array(
			self::STATUS_DRAFT     => __( 'Draft', 'ihumbak-invoices' ),
			self::STATUS_ISSUED    => __( 'Issued', 'ihumbak-invoices' ),
			self::STATUS_SENT      => __( 'Sent', 'ihumbak-invoices' ),
			self::STATUS_PAID      => __( 'Paid', 'ihumbak-invoices' ),
			self::STATUS_CANCELLED => __( 'Cancelled', 'ihumbak-invoices' ),
		);
	}

	/**
	 * Get status label.
	 *
	 * @return string
	 */
	public function getStatusLabel(): string {
		$statuses = self::getStatuses();
		return $statuses[ $this->status ] ?? $this->status;
	}

	/**
	 * Safely parse date string to DateTimeImmutable.
	 *
	 * @param string $date_string Date string to parse.
	 * @return DateTimeImmutable|null Parsed date or null on failure.
	 */
	protected static function parseDate( string $date_string ): ?DateTimeImmutable {
		try {
			return new DateTimeImmutable( $date_string );
		} catch ( \Exception $e ) {
			return null;
		}
	}
}
