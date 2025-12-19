<?php
/**
 * Document Item Model.
 *
 * @package IHumbak\Invoices\Models
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Models;

/**
 * Represents a single line item on a document.
 */
class DocumentItem {

	/**
	 * Item ID.
	 *
	 * @var int|null
	 */
	private ?int $id = null;

	/**
	 * Document ID.
	 *
	 * @var int|null
	 */
	private ?int $document_id = null;

	/**
	 * Product ID (from WooCommerce).
	 *
	 * @var int|null
	 */
	private ?int $product_id = null;

	/**
	 * Item name.
	 *
	 * @var string
	 */
	private string $name = '';

	/**
	 * Quantity.
	 *
	 * @var float
	 */
	private float $quantity = 1.0;

	/**
	 * Unit (szt., kg, etc.).
	 *
	 * @var string
	 */
	private string $unit = 'szt.';

	/**
	 * Unit price net.
	 *
	 * @var float
	 */
	private float $unit_price_net = 0.0;

	/**
	 * Unit price gross.
	 *
	 * @var float
	 */
	private float $unit_price_gross = 0.0;

	/**
	 * Tax rate (percentage).
	 *
	 * @var float
	 */
	private float $tax_rate = 23.0;

	/**
	 * Tax amount.
	 *
	 * @var float
	 */
	private float $tax_amount = 0.0;

	/**
	 * Line total net.
	 *
	 * @var float
	 */
	private float $line_total_net = 0.0;

	/**
	 * Line total gross.
	 *
	 * @var float
	 */
	private float $line_total_gross = 0.0;

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
	 * @param int|null $id Item ID.
	 * @return self
	 */
	public function setId( ?int $id ): self {
		$this->id = $id;
		return $this;
	}

	/**
	 * Get document ID.
	 *
	 * @return int|null
	 */
	public function getDocumentId(): ?int {
		return $this->document_id;
	}

	/**
	 * Set document ID.
	 *
	 * @param int|null $document_id Document ID.
	 * @return self
	 */
	public function setDocumentId( ?int $document_id ): self {
		$this->document_id = $document_id;
		return $this;
	}

	/**
	 * Get product ID.
	 *
	 * @return int|null
	 */
	public function getProductId(): ?int {
		return $this->product_id;
	}

	/**
	 * Set product ID.
	 *
	 * @param int|null $product_id Product ID.
	 * @return self
	 */
	public function setProductId( ?int $product_id ): self {
		$this->product_id = $product_id;
		return $this;
	}

	/**
	 * Get item name.
	 *
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Set item name.
	 *
	 * @param string $name Item name.
	 * @return self
	 */
	public function setName( string $name ): self {
		$this->name = $name;
		return $this;
	}

	/**
	 * Get quantity.
	 *
	 * @return float
	 */
	public function getQuantity(): float {
		return $this->quantity;
	}

	/**
	 * Set quantity.
	 *
	 * @param float $quantity Quantity.
	 * @return self
	 */
	public function setQuantity( float $quantity ): self {
		$this->quantity = $quantity;
		return $this;
	}

	/**
	 * Get unit.
	 *
	 * @return string
	 */
	public function getUnit(): string {
		return $this->unit;
	}

	/**
	 * Set unit.
	 *
	 * @param string $unit Unit.
	 * @return self
	 */
	public function setUnit( string $unit ): self {
		$this->unit = $unit;
		return $this;
	}

	/**
	 * Get unit price net.
	 *
	 * @return float
	 */
	public function getUnitPriceNet(): float {
		return $this->unit_price_net;
	}

	/**
	 * Set unit price net.
	 *
	 * @param float $price Unit price net.
	 * @return self
	 */
	public function setUnitPriceNet( float $price ): self {
		$this->unit_price_net = $price;
		return $this;
	}

	/**
	 * Get unit price gross.
	 *
	 * @return float
	 */
	public function getUnitPriceGross(): float {
		return $this->unit_price_gross;
	}

	/**
	 * Set unit price gross.
	 *
	 * @param float $price Unit price gross.
	 * @return self
	 */
	public function setUnitPriceGross( float $price ): self {
		$this->unit_price_gross = $price;
		return $this;
	}

	/**
	 * Get tax rate.
	 *
	 * @return float
	 */
	public function getTaxRate(): float {
		return $this->tax_rate;
	}

	/**
	 * Set tax rate.
	 *
	 * @param float $rate Tax rate percentage.
	 * @return self
	 */
	public function setTaxRate( float $rate ): self {
		$this->tax_rate = $rate;
		return $this;
	}

	/**
	 * Get tax amount.
	 *
	 * @return float
	 */
	public function getTaxAmount(): float {
		return $this->tax_amount;
	}

	/**
	 * Set tax amount.
	 *
	 * @param float $amount Tax amount.
	 * @return self
	 */
	public function setTaxAmount( float $amount ): self {
		$this->tax_amount = $amount;
		return $this;
	}

	/**
	 * Get line total net.
	 *
	 * @return float
	 */
	public function getLineTotalNet(): float {
		return $this->line_total_net;
	}

	/**
	 * Set line total net.
	 *
	 * @param float $total Line total net.
	 * @return self
	 */
	public function setLineTotalNet( float $total ): self {
		$this->line_total_net = $total;
		return $this;
	}

	/**
	 * Get line total gross.
	 *
	 * @return float
	 */
	public function getLineTotalGross(): float {
		return $this->line_total_gross;
	}

	/**
	 * Set line total gross.
	 *
	 * @param float $total Line total gross.
	 * @return self
	 */
	public function setLineTotalGross( float $total ): self {
		$this->line_total_gross = $total;
		return $this;
	}

	/**
	 * Create from array.
	 *
	 * @param array<string, mixed> $data Item data.
	 * @return self
	 */
	public static function fromArray( array $data ): self {
		$item = new self();

		if ( isset( $data['id'] ) ) {
			$item->setId( (int) $data['id'] );
		}
		if ( isset( $data['document_id'] ) ) {
			$item->setDocumentId( (int) $data['document_id'] );
		}
		if ( isset( $data['product_id'] ) ) {
			$item->setProductId( (int) $data['product_id'] );
		}

		$item->setName( (string) ( $data['name'] ?? '' ) );
		$item->setQuantity( (float) ( $data['quantity'] ?? 1.0 ) );
		$item->setUnit( (string) ( $data['unit'] ?? 'szt.' ) );
		$item->setUnitPriceNet( (float) ( $data['unit_price_net'] ?? 0.0 ) );
		$item->setUnitPriceGross( (float) ( $data['unit_price_gross'] ?? 0.0 ) );
		$item->setTaxRate( (float) ( $data['tax_rate'] ?? 23.0 ) );
		$item->setTaxAmount( (float) ( $data['tax_amount'] ?? 0.0 ) );
		$item->setLineTotalNet( (float) ( $data['line_total_net'] ?? 0.0 ) );
		$item->setLineTotalGross( (float) ( $data['line_total_gross'] ?? 0.0 ) );

		return $item;
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'id'               => $this->id,
			'document_id'      => $this->document_id,
			'product_id'       => $this->product_id,
			'name'             => $this->name,
			'quantity'         => $this->quantity,
			'unit'             => $this->unit,
			'unit_price_net'   => $this->unit_price_net,
			'unit_price_gross' => $this->unit_price_gross,
			'tax_rate'         => $this->tax_rate,
			'tax_amount'       => $this->tax_amount,
			'line_total_net'   => $this->line_total_net,
			'line_total_gross' => $this->line_total_gross,
		);
	}
}
