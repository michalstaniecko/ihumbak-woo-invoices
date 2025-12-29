<?php
/**
 * Invoice email template (Plain text).
 *
 * @package IHumbak\Invoices
 * @var \IHumbak\Invoices\Models\Document $document
 * @var string $email_heading
 * @var bool $sent_to_admin
 * @var bool $plain_text
 * @var \IHumbak\Invoices\Modules\Email\InvoiceEmail $email
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

$customer_name = '';
if ( $document->getOrderId() ) {
	$order = wc_get_order( $document->getOrderId() );
	if ( $order ) {
		$customer_name = ' ' . $order->get_billing_first_name();
	}
}

printf(
	/* translators: %s: Customer first name */
	esc_html__( 'Hi%s,', 'ihumbak-invoices' ),
	esc_html( $customer_name )
);
echo "\n\n";

printf(
	/* translators: %s: Document number */
	esc_html__( 'Please find attached your invoice %s.', 'ihumbak-invoices' ),
	esc_html( $document->getDocumentNumber() )
);
echo "\n\n";

if ( $document->getOrderId() ) {
	$order = wc_get_order( $document->getOrderId() );
	if ( $order ) {
		printf(
			/* translators: %s: Order number */
			esc_html__( 'This invoice is for order %s.', 'ihumbak-invoices' ),
			'#' . esc_html( $order->get_order_number() )
		);
		echo "\n\n";
	}
}

echo "----------------------------------------\n";
echo esc_html__( 'Invoice Details', 'ihumbak-invoices' );
echo "\n----------------------------------------\n\n";

echo esc_html__( 'Invoice Number', 'ihumbak-invoices' ) . ': ' . esc_html( $document->getDocumentNumber() ) . "\n";
echo esc_html__( 'Issue Date', 'ihumbak-invoices' ) . ': ' . esc_html( $document->getIssueDate() ? $document->getIssueDate()->format( 'Y-m-d' ) : '-' ) . "\n";
echo esc_html__( 'Total Amount', 'ihumbak-invoices' ) . ': ' . esc_html( number_format( $document->getTotal(), 2, '.', ' ' ) . ' ' . $document->getCurrency() ) . "\n";

if ( $document->getDueDate() ) {
	echo esc_html__( 'Due Date', 'ihumbak-invoices' ) . ': ' . esc_html( $document->getDueDate()->format( 'Y-m-d' ) ) . "\n";
}

if ( $document->getPaymentMethodDisplayName() ) {
	echo esc_html__( 'Payment Method', 'ihumbak-invoices' ) . ': ' . esc_html( $document->getPaymentMethodDisplayName() ) . "\n";
}

echo "\n";
echo esc_html__( 'The invoice PDF is attached to this email.', 'ihumbak-invoices' );
echo "\n\n";

/**
 * Show user-defined additional content.
 */
if ( $additional_content = $email->get_option( 'additional_content' ) ) {
	echo "----------------------------------------\n\n";
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
