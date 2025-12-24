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

// Load shared mocks and Composer autoloader.
require_once __DIR__ . '/mocks.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ============================================================================
// Configuration
// ============================================================================

$output_dir  = __DIR__ . '/../tests/output';
$output_file = $output_dir . '/test-receipt.pdf';

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

// Receipt can have minimal buyer data (individual customer).
$buyer = new MockBuyer(
	array(
		'name'     => 'Jan Kowalski',
		'address'  => null,
		'postcode' => '',
		'city'     => '',
		'country'  => '',
		'nip'      => null,
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

// Load styles.
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

// Save to file.
file_put_contents( $output_file, $dompdf->output() );

echo "Receipt PDF generated successfully!\n";
echo "Output: {$output_file}\n";
echo "File size: " . number_format( filesize( $output_file ) ) . " bytes\n";
