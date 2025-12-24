<?php
/**
 * Test PDF Generator Script
 *
 * Generates a test invoice PDF using the real invoice.php template.
 *
 * Usage: php scripts/generate-test-pdf.php
 *
 * @package IHumbak\Invoices
 */

// Load shared mocks and Composer autoloader.
require_once __DIR__ . '/mocks.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ============================================================================
// Configuration
// ============================================================================

$output_dir  = __DIR__ . '/../tests/output';
$output_file = $output_dir . '/test-invoice.pdf';

// Ensure output directory exists.
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

$buyer = new MockBuyer(
	array(
		'name'     => 'Customer Corp Ltd.',
		'address'  => 'ul. Kliencka 99/10',
		'postcode' => '30-002',
		'city'     => 'Kraków',
		'country'  => 'Poland',
		'nip'      => 'PL9876543210',
	)
);

$document = new MockInvoice(
	array(
		'number'         => 'FV/2025/12/0001',
		'issue_date'     => '2025-12-21',
		'sale_date'      => '2025-12-21',
		'due_date'       => '2025-12-28',
		'order_id'       => 1234,
		'payment_method' => 'transfer',
		'currency'       => 'PLN',
		'subtotal'       => 2650.00,
		'tax_total'      => 609.50,
		'total'          => 3259.50,
		'notes'          => 'Thank you for your business! Payment due within 7 days.',
		'status'         => 'issued',
	)
);

$items = array(
	new MockDocumentItem(
		array(
			'name'       => 'Web Development Services - December 2025',
			'sku'        => 'SVC-WEB-DEV-2025',
			'quantity'   => 40,
			'unit'       => 'h',
			'unit_net'   => 50.00,
			'tax_rate'   => 23,
			'line_net'   => 2000.00,
			'line_tax'   => 460.00,
			'line_gross' => 2460.00,
		)
	),
	new MockDocumentItem(
		array(
			'name'       => 'Annual Hosting Package - Premium',
			'sku'        => 'HOST-PREM-12M',
			'quantity'   => 1,
			'unit'       => 'szt',
			'unit_net'   => 500.00,
			'tax_rate'   => 23,
			'line_net'   => 500.00,
			'line_tax'   => 115.00,
			'line_gross' => 615.00,
		)
	),
	new MockDocumentItem(
		array(
			'name'       => 'SSL Certificate - 1 Year',
			'sku'        => null,
			'quantity'   => 1,
			'unit'       => 'szt',
			'unit_net'   => 150.00,
			'tax_rate'   => 23,
			'line_net'   => 150.00,
			'line_tax'   => 34.50,
			'line_gross' => 184.50,
		)
	),
);

$vat_breakdown = array(
	array(
		'rate'  => 23,
		'net'   => 2650.00,
		'tax'   => 609.50,
		'gross' => 3259.50,
	),
);

$settings = array(
	'pdf' => array(
		'footer_text' => 'This invoice was generated electronically and is valid without a signature.',
	),
);

$logo_url  = null;
$formatted = array();

// Load styles.
$styles = file_get_contents( __DIR__ . '/../templates/pdf/default/styles.css' );

// ============================================================================
// Render Template
// ============================================================================

ob_start();
include __DIR__ . '/../templates/pdf/default/invoice.php';
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

// Save to file.
file_put_contents( $output_file, $dompdf->output() );

echo "PDF generated successfully!\n";
echo "Output: {$output_file}\n";
echo "File size: " . number_format( filesize( $output_file ) ) . " bytes\n";
