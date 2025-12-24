<?php
/**
 * Test Credit Note PDF Generator Script
 *
 * Generates a test credit note PDF using the real credit-note.php template.
 *
 * Usage: php scripts/generate-test-credit-note.php
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
$output_file = $output_dir . '/test-credit-note.pdf';

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

// Original invoice being corrected.
$original_document = new MockOriginalDocument(
	array(
		'number'     => 'FV/2025/12/0001',
		'issue_date' => '2025-12-15',
		'currency'   => 'PLN',
		'total'      => 1230.00,
	)
);

// Credit note document (partial correction - returning 1 item).
$document = new MockCreditNote(
	array(
		'number'             => 'CN/2025/12/0001',
		'issue_date'         => '2025-12-21',
		'sale_date'          => '2025-12-21',
		'order_id'           => 1234,
		'currency'           => 'PLN',
		'subtotal'           => -406.50,
		'tax_total'          => -93.50,
		'total'              => -500.00,
		'notes'              => 'Refund processed. Amount will be credited to your account within 14 business days.',
		'status'             => 'issued',
		'is_full_correction' => false,
		'correction_reason'  => 'Customer returned defective product (Wireless Bluetooth Headphones). Full refund issued as per warranty policy.',
	)
);

// Items on credit note (negative values - represents what is being returned/credited).
$items = array(
	new MockDocumentItem(
		array(
			'name'       => 'Wireless Bluetooth Headphones - RETURNED',
			'sku'        => 'AUDIO-BT-001',
			'quantity'   => -1,
			'unit'       => 'szt',
			'unit_net'   => 406.50,
			'tax_rate'   => 23,
			'line_net'   => -406.50,
			'line_tax'   => -93.50,
			'line_gross' => -500.00,
		)
	),
);

$original_items = array();

$vat_breakdown = array(
	array(
		'rate'  => 23,
		'net'   => -406.50,
		'tax'   => -93.50,
		'gross' => -500.00,
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
include __DIR__ . '/../templates/pdf/default/credit-note.php';
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

echo "Credit Note PDF generated successfully!\n";
echo "Output: {$output_file}\n";
echo "File size: " . number_format( filesize( $output_file ) ) . " bytes\n";
