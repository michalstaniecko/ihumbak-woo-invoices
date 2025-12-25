<?php
/**
 * Mock Classes for PDF Test Scripts
 *
 * Shared mock classes used by generate-test-*.php scripts.
 *
 * @package IHumbak\Invoices
 */

// ============================================================================
// WordPress Mock Functions
// ============================================================================

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * Mock esc_html function.
	 *
	 * @param string $text Text to escape.
	 * @return string Escaped text.
	 */
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * Mock esc_attr function.
	 *
	 * @param string $text Text to escape.
	 * @return string Escaped text.
	 */
	function esc_attr( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Mock esc_html__ function.
	 *
	 * @param string $text   Text to escape.
	 * @param string $domain Text domain.
	 * @return string Escaped text.
	 */
	function esc_html__( string $text, string $domain = 'default' ): string {
		return esc_html( $text );
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	/**
	 * Mock esc_html_e function.
	 *
	 * @param string $text   Text to escape and echo.
	 * @param string $domain Text domain.
	 * @return void
	 */
	function esc_html_e( string $text, string $domain = 'default' ): void {
		echo esc_html( $text );
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Mock __ function.
	 *
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain.
	 * @return string Translated text.
	 */
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

// ============================================================================
// Mock Model Classes
// ============================================================================

/**
 * Mock Seller class
 */
class MockSeller {
	/**
	 * Seller data.
	 *
	 * @var array<string, mixed>
	 */
	private array $data;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $data Seller data.
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	public function getName(): string {
		return $this->data['name'] ?? '';
	}

	public function getAddress(): string {
		return $this->data['address'] ?? '';
	}

	public function getPostcode(): string {
		return $this->data['postcode'] ?? '';
	}

	public function getCity(): string {
		return $this->data['city'] ?? '';
	}

	public function getCountry(): string {
		return $this->data['country'] ?? '';
	}

	public function getNip(): ?string {
		return $this->data['nip'] ?? null;
	}

	public function getBankName(): ?string {
		return $this->data['bank_name'] ?? null;
	}

	public function getBankAccount(): ?string {
		return $this->data['bank_account'] ?? null;
	}

	public function getEmail(): ?string {
		return $this->data['email'] ?? null;
	}

	public function getPhone(): ?string {
		return $this->data['phone'] ?? null;
	}
}

/**
 * Mock Buyer class
 */
class MockBuyer {
	/**
	 * Buyer data.
	 *
	 * @var array<string, mixed>
	 */
	private array $data;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $data Buyer data.
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	public function getName(): string {
		return $this->data['name'] ?? '';
	}

	public function getAddress(): ?string {
		return $this->data['address'] ?? null;
	}

	public function getPostcode(): string {
		return $this->data['postcode'] ?? '';
	}

	public function getCity(): string {
		return $this->data['city'] ?? '';
	}

	public function getCountry(): string {
		return $this->data['country'] ?? '';
	}

	public function getNip(): ?string {
		return $this->data['nip'] ?? null;
	}
}

/**
 * Mock DocumentItem class
 */
class MockDocumentItem {
	/**
	 * Item data.
	 *
	 * @var array<string, mixed>
	 */
	private array $data;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $data Item data.
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	public function getName(): string {
		return $this->data['name'] ?? '';
	}

	public function getSku(): ?string {
		return $this->data['sku'] ?? null;
	}

	public function getQuantity(): float {
		return $this->data['quantity'] ?? 0;
	}

	public function getUnit(): string {
		return $this->data['unit'] ?? 'szt';
	}

	public function getUnitPriceNet(): float {
		return $this->data['unit_net'] ?? 0;
	}

	public function getTaxRate(): float {
		return $this->data['tax_rate'] ?? 0;
	}

	public function getLineTotalNet(): float {
		return $this->data['line_net'] ?? 0;
	}

	public function getTaxAmount(): float {
		return $this->data['line_tax'] ?? 0;
	}

	public function getLineTotalGross(): float {
		return $this->data['line_gross'] ?? 0;
	}
}

/**
 * Mock Invoice class
 */
class MockInvoice {
	/**
	 * Invoice data.
	 *
	 * @var array<string, mixed>
	 */
	private array $data;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $data Invoice data.
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	public function getDocumentNumber(): string {
		return $this->data['number'] ?? '';
	}

	public function getIssueDate(): ?\DateTimeImmutable {
		return isset( $this->data['issue_date'] ) ? new \DateTimeImmutable( $this->data['issue_date'] ) : null;
	}

	public function getSaleDate(): ?\DateTimeImmutable {
		return isset( $this->data['sale_date'] ) ? new \DateTimeImmutable( $this->data['sale_date'] ) : null;
	}

	public function getDueDate(): ?\DateTimeImmutable {
		return isset( $this->data['due_date'] ) ? new \DateTimeImmutable( $this->data['due_date'] ) : null;
	}

	public function getOrderId(): ?int {
		return $this->data['order_id'] ?? null;
	}

	public function getPaymentMethod(): ?string {
		return $this->data['payment_method'] ?? null;
	}

	public function getCurrency(): string {
		return $this->data['currency'] ?? 'PLN';
	}

	public function getSubtotal(): float {
		return $this->data['subtotal'] ?? 0;
	}

	public function getTaxTotal(): float {
		return $this->data['tax_total'] ?? 0;
	}

	public function getTotal(): float {
		return $this->data['total'] ?? 0;
	}

	public function getNotes(): ?string {
		return $this->data['notes'] ?? null;
	}

	public function getStatus(): string {
		return $this->data['status'] ?? 'draft';
	}
}

/**
 * Mock Receipt class
 */
class MockReceipt {
	/**
	 * Receipt data.
	 *
	 * @var array<string, mixed>
	 */
	private array $data;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $data Receipt data.
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	public function getDocumentNumber(): string {
		return $this->data['number'] ?? '';
	}

	public function getIssueDate(): ?\DateTimeImmutable {
		return isset( $this->data['issue_date'] ) ? new \DateTimeImmutable( $this->data['issue_date'] ) : null;
	}

	public function getSaleDate(): ?\DateTimeImmutable {
		return isset( $this->data['sale_date'] ) ? new \DateTimeImmutable( $this->data['sale_date'] ) : null;
	}

	public function getOrderId(): ?int {
		return $this->data['order_id'] ?? null;
	}

	public function getCurrency(): string {
		return $this->data['currency'] ?? 'PLN';
	}

	public function getSubtotal(): float {
		return $this->data['subtotal'] ?? 0;
	}

	public function getTaxTotal(): float {
		return $this->data['tax_total'] ?? 0;
	}

	public function getTotal(): float {
		return $this->data['total'] ?? 0;
	}

	public function getNotes(): ?string {
		return $this->data['notes'] ?? null;
	}

	public function getStatus(): string {
		return $this->data['status'] ?? 'draft';
	}

	public function getPaymentMethod(): ?string {
		return $this->data['payment_method'] ?? null;
	}
}

/**
 * Mock Original Document class (the invoice being corrected)
 */
class MockOriginalDocument {
	/**
	 * Document data.
	 *
	 * @var array<string, mixed>
	 */
	private array $data;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $data Document data.
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	public function getDocumentNumber(): string {
		return $this->data['number'] ?? '';
	}

	public function getIssueDate(): ?\DateTimeImmutable {
		return isset( $this->data['issue_date'] ) ? new \DateTimeImmutable( $this->data['issue_date'] ) : null;
	}

	public function getCurrency(): string {
		return $this->data['currency'] ?? 'PLN';
	}

	public function getTotal(): float {
		return $this->data['total'] ?? 0;
	}
}

/**
 * Mock CreditNote class
 */
class MockCreditNote {
	/**
	 * Credit note data.
	 *
	 * @var array<string, mixed>
	 */
	private array $data;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $data Credit note data.
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	public function getDocumentNumber(): string {
		return $this->data['number'] ?? '';
	}

	public function getIssueDate(): ?\DateTimeImmutable {
		return isset( $this->data['issue_date'] ) ? new \DateTimeImmutable( $this->data['issue_date'] ) : null;
	}

	public function getSaleDate(): ?\DateTimeImmutable {
		return isset( $this->data['sale_date'] ) ? new \DateTimeImmutable( $this->data['sale_date'] ) : null;
	}

	public function getOrderId(): ?int {
		return $this->data['order_id'] ?? null;
	}

	public function getCurrency(): string {
		return $this->data['currency'] ?? 'PLN';
	}

	public function getSubtotal(): float {
		return $this->data['subtotal'] ?? 0;
	}

	public function getTaxTotal(): float {
		return $this->data['tax_total'] ?? 0;
	}

	public function getTotal(): float {
		return $this->data['total'] ?? 0;
	}

	public function getNotes(): ?string {
		return $this->data['notes'] ?? null;
	}

	public function getStatus(): string {
		return $this->data['status'] ?? 'draft';
	}

	public function isFullCorrection(): bool {
		return $this->data['is_full_correction'] ?? false;
	}

	public function getCorrectionReason(): ?string {
		return $this->data['correction_reason'] ?? null;
	}
}
