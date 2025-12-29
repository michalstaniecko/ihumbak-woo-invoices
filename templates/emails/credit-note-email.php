<?php
/**
 * Credit note email template (HTML).
 *
 * @package IHumbak\Invoices
 * @var \IHumbak\Invoices\Models\CreditNote $document
 * @var string $email_heading
 * @var bool $sent_to_admin
 * @var bool $plain_text
 * @var \IHumbak\Invoices\Modules\Email\CreditNoteEmail $email
 */

defined( 'ABSPATH' ) || exit;

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>
	<?php
	printf(
		/* translators: %s: Customer first name */
		esc_html__( 'Hi%s,', 'ihumbak-invoices' ),
		$document->getOrderId() ? ' ' . esc_html( wc_get_order( $document->getOrderId() )->get_billing_first_name() ) : ''
	);
	?>
</p>

<p>
	<?php
	printf(
		/* translators: %s: Document number */
		esc_html__( 'Please find attached your credit note %s.', 'ihumbak-invoices' ),
		'<strong>' . esc_html( $document->getDocumentNumber() ) . '</strong>'
	);
	?>
</p>

<?php if ( $document->getOrderId() ) : ?>
	<?php $order = wc_get_order( $document->getOrderId() ); ?>
	<?php if ( $order ) : ?>
		<p>
			<?php
			printf(
				/* translators: %s: Order number */
				esc_html__( 'This credit note is for order %s.', 'ihumbak-invoices' ),
				'<strong>#' . esc_html( $order->get_order_number() ) . '</strong>'
			);
			?>
		</p>
	<?php endif; ?>
<?php endif; ?>

<h2><?php esc_html_e( 'Credit Note Details', 'ihumbak-invoices' ); ?></h2>

<table cellspacing="0" cellpadding="6" border="1" style="width: 100%; border: 1px solid #e5e5e5; margin-bottom: 20px;">
	<tbody>
		<tr>
			<th scope="row" style="text-align: left; border: 1px solid #e5e5e5; padding: 12px;">
				<?php esc_html_e( 'Credit Note Number', 'ihumbak-invoices' ); ?>
			</th>
			<td style="text-align: left; border: 1px solid #e5e5e5; padding: 12px;">
				<?php echo esc_html( $document->getDocumentNumber() ); ?>
			</td>
		</tr>
		<tr>
			<th scope="row" style="text-align: left; border: 1px solid #e5e5e5; padding: 12px;">
				<?php esc_html_e( 'Issue Date', 'ihumbak-invoices' ); ?>
			</th>
			<td style="text-align: left; border: 1px solid #e5e5e5; padding: 12px;">
				<?php echo esc_html( $document->getIssueDate() ? $document->getIssueDate()->format( 'Y-m-d' ) : '-' ); ?>
			</td>
		</tr>
		<tr>
			<th scope="row" style="text-align: left; border: 1px solid #e5e5e5; padding: 12px;">
				<?php esc_html_e( 'Credit Amount', 'ihumbak-invoices' ); ?>
			</th>
			<td style="text-align: left; border: 1px solid #e5e5e5; padding: 12px;">
				<strong><?php echo esc_html( number_format( abs( $document->getTotal() ), 2, '.', ' ' ) . ' ' . $document->getCurrency() ); ?></strong>
			</td>
		</tr>
		<?php if ( method_exists( $document, 'getCorrectionReason' ) && $document->getCorrectionReason() ) : ?>
		<tr>
			<th scope="row" style="text-align: left; border: 1px solid #e5e5e5; padding: 12px;">
				<?php esc_html_e( 'Reason', 'ihumbak-invoices' ); ?>
			</th>
			<td style="text-align: left; border: 1px solid #e5e5e5; padding: 12px;">
				<?php echo esc_html( $document->getCorrectionReason() ); ?>
			</td>
		</tr>
		<?php endif; ?>
	</tbody>
</table>

<p>
	<?php esc_html_e( 'The credit note PDF is attached to this email.', 'ihumbak-invoices' ); ?>
</p>

<?php
/**
 * Show user-defined additional content.
 */
if ( $additional_content = $email->get_option( 'additional_content' ) ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
