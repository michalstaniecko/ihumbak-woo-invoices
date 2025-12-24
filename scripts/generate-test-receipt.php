<?php
/**
 * Test Receipt PDF Generator Script
 *
 * Generates a test receipt PDF using the real receipt.php template.
 *
 * Usage: php scripts/generate-test-receipt.php
 *
 * @package IHumbak\Invoices
 */

// ============================================================================
// WordPress Mock Functions
// ============================================================================

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return esc_html( $text );
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( $text, $domain = 'default' ) {
		echo esc_html( $text );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
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
	private array $data;

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
	private array $data;

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
	private array $data;

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
 * Mock Receipt class
 */
class MockReceipt {
	private array $data;

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
}

// ============================================================================
// Configuration
// ============================================================================

require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$output_dir  = __DIR__ . '/../tests/output';
$output_file = $output_dir . '/test-receipt.pdf';

// Ensure output directory exists
if ( ! is_dir( $output_dir ) ) {
	mkdir( $output_dir, 0755, true );
}

// ============================================================================
// Test Data
// ============================================================================

$seller = new MockSeller(
	array(
		'name'         => 'Example Company Sp. z o.o.',
		'address'      => 'ul. Testowa 123/45',
		'postcode'     => '00-001',
		'city'         => 'Warszawa',
		'country'      => 'Poland',
		'nip'          => 'PL1234567890',
		'bank_name'    => 'Example Bank S.A.',
		'bank_account' => 'PL 12 3456 7890 1234 5678 9012 3456',
		'email'        => 'contact@example.com',
		'phone'        => '+48 123 456 789',
	)
);

// Receipt can have minimal buyer data (individual customer)
$buyer = new MockBuyer(
	array(
		'name'     => 'Jan Kowalski',
		'address'  => null, // Individual customer - no address required
		'postcode' => '',
		'city'     => '',
		'country'  => '',
		'nip'      => null, // No VAT ID for individual
	)
);

$document = new MockReceipt(
	array(
		'number'     => 'PAR/2025/12/0001',
		'issue_date' => '2025-12-21',
		'sale_date'  => '2025-12-21',
		'order_id'   => 5678,
		'currency'   => 'PLN',
		'subtotal'   => 324.39,
		'tax_total'  => 74.61,
		'total'      => 399.00,
		'notes'      => null,
		'status'     => 'issued',
	)
);

$items = array(
	new MockDocumentItem(
		array(
			'name'       => 'Wireless Bluetooth Headphones',
			'sku'        => 'AUDIO-BT-001',
			'quantity'   => 1,
			'unit'       => 'szt',
			'unit_net'   => 203.25,
			'tax_rate'   => 23,
			'line_net'   => 203.25,
			'line_tax'   => 46.75,
			'line_gross' => 250.00,
		)
	),
	new MockDocumentItem(
		array(
			'name'       => 'USB-C Charging Cable 2m',
			'sku'        => 'CABLE-USBC-2M',
			'quantity'   => 2,
			'unit'       => 'szt',
			'unit_net'   => 32.52,
			'tax_rate'   => 23,
			'line_net'   => 65.04,
			'line_tax'   => 14.96,
			'line_gross' => 80.00,
		)
	),
	new MockDocumentItem(
		array(
			'name'       => 'Screen Protector',
			'sku'        => null,
			'quantity'   => 1,
			'unit'       => 'szt',
			'unit_net'   => 56.10,
			'tax_rate'   => 23,
			'line_net'   => 56.10,
			'line_tax'   => 12.90,
			'line_gross' => 69.00,
		)
	),
);

$vat_breakdown = array(
	array(
		'rate'  => 23,
		'net'   => 324.39,
		'tax'   => 74.61,
		'gross' => 399.00,
	),
);

$settings = array(
	'pdf' => array(
		'footer_text' => '',
	),
);

$logo_url  = null;
$formatted = array();

// Load styles
$styles = file_get_contents( __DIR__ . '/../templates/pdf/default/styles.css' );

// ============================================================================
// Render Template
// ============================================================================

ob_start();
include __DIR__ . '/../templates/pdf/default/receipt.php';
$html = ob_get_clean();

// ============================================================================
// Generate PDF
// ============================================================================

$options = new Options();
$options->set( 'isRemoteEnabled', true );
$options->set( 'isFontSubsettingEnabled', true );
$options->set( 'defaultFont', 'DejaVu Sans' );

$dompdf = new Dompdf( $options );
$dompdf->loadHtml( $html );
$dompdf->setPaper( 'A4', 'portrait' );
$dompdf->render();

// Save to file
file_put_contents( $output_file, $dompdf->output() );

echo "Receipt PDF generated successfully!\n";
echo "Output: {$output_file}\n";
echo "File size: " . number_format( filesize( $output_file ) ) . " bytes\n";
