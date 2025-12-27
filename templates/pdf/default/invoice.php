<?php
/**
 * Default Invoice PDF Template
 *
 * EU Standard VAT Invoice Template - English
 *
 * Available variables:
 *
 * @var IHumbak\Invoices\Models\Invoice $document
 * @var IHumbak\Invoices\Models\Seller|null $seller
 * @var IHumbak\Invoices\Models\Buyer|null $buyer
 * @var IHumbak\Invoices\Models\DocumentItem[] $items
 * @var array $settings
 * @var string|null $logo_url
 * @var string $styles
 * @var array $vat_breakdown
 * @var array $formatted
 *
 * @package IHumbak\Invoices
 */

defined( 'ABSPATH' ) || exit;

$currency = $document->getCurrency();
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<?php // translators: %s: Document number ?>
	<title><?php echo esc_html( sprintf( __( 'Invoice %s', 'ihumbak-invoices' ), $document->getDocumentNumber() ) ); ?></title>
	<style>
		<?php echo $styles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</style>
</head>
<body>
	<div class="document-container">
		<!-- Row 1: Logo/Company Name + Seller Data -->
		<table class="header-section" cellpadding="0" cellspacing="0">
			<tr>
				<td class="header-left">
					<?php if ( $logo_url ) : ?>
						<img src="<?php echo esc_attr( $logo_url ); ?>" alt="Logo" class="logo">
					<?php elseif ( $seller ) : ?>
						<div class="company-name"><?php echo esc_html( $seller->getName() ); ?></div>
					<?php endif; ?>
				</td>
				<td class="header-right">
					<?php if ( $seller ) : ?>
						<div class="seller-info">
							<strong><?php echo esc_html( $seller->getName() ); ?></strong><br>
							<?php echo nl2br( esc_html( $seller->getDetails() ) ); ?>
						</div>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<!-- Row 2: Centered Document Title -->
		<div class="document-title-section">
			<div class="document-title"><?php esc_html_e( 'VAT Invoice', 'ihumbak-invoices' ); ?></div>
			<div class="document-number"><?php echo esc_html( $document->getDocumentNumber() ); ?></div>
		</div>

		<!-- Row 3: Invoice Details + Buyer Data -->
		<table class="details-section" cellpadding="0" cellspacing="0">
			<tr>
				<td class="details-left">
					<div class="detail-box">
						<div class="detail-box-title"><?php esc_html_e( 'Invoice Details', 'ihumbak-invoices' ); ?></div>
						<div class="detail-row">
							<span class="label"><?php esc_html_e( 'Invoice No:', 'ihumbak-invoices' ); ?></span>
							<span class="value"><?php echo esc_html( $document->getDocumentNumber() ); ?></span>
						</div>
						<div class="detail-row">
							<span class="label"><?php esc_html_e( 'Issue Date:', 'ihumbak-invoices' ); ?></span>
							<span class="value"><?php echo $document->getIssueDate() ? esc_html( $document->getIssueDate()->format( 'Y-m-d' ) ) : '-'; ?></span>
						</div>
						<div class="detail-row">
							<span class="label"><?php esc_html_e( 'Sale Date:', 'ihumbak-invoices' ); ?></span>
							<span class="value"><?php echo $document->getSaleDate() ? esc_html( $document->getSaleDate()->format( 'Y-m-d' ) ) : '-'; ?></span>
						</div>
						<?php if ( $document->getDueDate() ) : ?>
							<div class="detail-row">
								<span class="label"><?php esc_html_e( 'Due Date:', 'ihumbak-invoices' ); ?></span>
								<span class="value"><?php echo esc_html( $document->getDueDate()->format( 'Y-m-d' ) ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $document->getPaymentDate() ) : ?>
							<div class="detail-row">
								<span class="label"><?php esc_html_e( 'Payment Date:', 'ihumbak-invoices' ); ?></span>
								<span class="value"><?php echo esc_html( $document->getPaymentDate()->format( 'Y-m-d' ) ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $document->getOrderId() ) : ?>
							<div class="detail-row">
								<span class="label"><?php esc_html_e( 'Order No:', 'ihumbak-invoices' ); ?></span>
								<span class="value">#<?php echo esc_html( $document->getOrderId() ); ?></span>
							</div>
						<?php endif; ?>
						<?php
						$payment_display_name = $document->getPaymentMethodDisplayName();
						$is_paid_online       = $document->getPaymentDate() && 'online' === $document->getPaymentMethod();
						?>
						<?php if ( $is_paid_online && $payment_display_name ) : ?>
							<div class="detail-row paid-online">
								<span class="label"><?php esc_html_e( 'Paid via:', 'ihumbak-invoices' ); ?></span>
								<span class="value"><?php echo esc_html( $payment_display_name ); ?></span>
							</div>
						<?php else : ?>
							<div class="detail-row">
								<span class="label"><?php esc_html_e( 'Amount Due:', 'ihumbak-invoices' ); ?></span>
								<span class="value"><?php echo esc_html( number_format( $document->getTotal(), 2, '.', ' ' ) . ' ' . $currency ); ?></span>
							</div>
							<?php if ( $payment_display_name ) : ?>
								<div class="detail-row">
									<span class="label"><?php esc_html_e( 'Payment:', 'ihumbak-invoices' ); ?></span>
									<span class="value"><?php echo esc_html( $payment_display_name ); ?></span>
								</div>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</td>
				<td class="details-right">
					<div class="detail-box">
						<div class="detail-box-title"><?php esc_html_e( 'Buyer', 'ihumbak-invoices' ); ?></div>
						<?php if ( $buyer ) : ?>
							<div class="party-name"><?php echo esc_html( $buyer->getName() ); ?></div>
							<div class="party-address">
								<?php echo esc_html( $buyer->getAddress() ); ?><br>
								<?php echo esc_html( $buyer->getPostcode() . ' ' . $buyer->getCity() ); ?><br>
								<?php echo esc_html( $buyer->getCountry() ); ?>
							</div>
							<?php if ( $buyer->getNip() ) : ?>
								<div class="party-tax-id"><?php esc_html_e( 'VAT ID:', 'ihumbak-invoices' ); ?> <?php echo esc_html( $buyer->getNip() ); ?></div>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</td>
			</tr>
		</table>

		<!-- Items Table -->
		<div class="items-section">
			<table class="items-table" cellpadding="0" cellspacing="0">
				<thead>
					<tr>
						<th class="col-name"><?php esc_html_e( 'Description', 'ihumbak-invoices' ); ?></th>
						<th class="col-qty text-right"><?php esc_html_e( 'Qty', 'ihumbak-invoices' ); ?></th>
						<th class="col-net text-right"><?php printf( esc_html__( 'Net (%s)', 'ihumbak-invoices' ), esc_html( $currency ) ); ?></th>
						<th class="col-tax-rate text-center"><?php esc_html_e( 'VAT %', 'ihumbak-invoices' ); ?></th>
						<th class="col-tax text-right"><?php printf( esc_html__( 'VAT (%s)', 'ihumbak-invoices' ), esc_html( $currency ) ); ?></th>
						<th class="col-gross text-right"><?php printf( esc_html__( 'Gross (%s)', 'ihumbak-invoices' ), esc_html( $currency ) ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $items as $item ) : ?>
						<tr>
							<td class="item-name">
								<?php echo esc_html( $item->getName() ); ?>
								<?php if ( $item->getSku() ) : ?>
									<div class="item-sku"><?php esc_html_e( 'SKU:', 'ihumbak-invoices' ); ?> <?php echo esc_html( $item->getSku() ); ?></div>
								<?php endif; ?>
							</td>
							<td class="text-right"><?php echo esc_html( number_format( $item->getQuantity(), 2, '.', '' ) ); ?></td>
							<td class="text-right"><?php echo esc_html( number_format( $item->getLineTotalNet(), 2, '.', ' ' ) ); ?></td>
							<td class="text-center"><?php echo esc_html( number_format( $item->getTaxRate(), 0 ) ); ?>%</td>
							<td class="text-right"><?php echo esc_html( number_format( $item->getTaxAmount(), 2, '.', ' ' ) ); ?></td>
							<td class="text-right"><?php echo esc_html( number_format( $item->getLineTotalGross(), 2, '.', ' ' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
				<?php if ( ! empty( $vat_breakdown ) ) : ?>
				<tfoot>
					<?php foreach ( $vat_breakdown as $values ) : ?>
						<tr class="vat-subtotal">
							<td colspan="2"></td>
							<td class="text-right"><?php echo esc_html( number_format( $values['net'], 2, '.', ' ' ) ); ?></td>
							<td class="text-center"><?php echo esc_html( number_format( $values['rate'], 0 ) ); ?>%</td>
							<td class="text-right"><?php echo esc_html( number_format( $values['tax'], 2, '.', ' ' ) ); ?></td>
							<td class="text-right"><?php echo esc_html( number_format( $values['gross'], 2, '.', ' ' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					<tr class="grand-total">
						<td colspan="2"></td>
						<td class="text-right"><?php echo esc_html( number_format( $document->getSubtotal(), 2, '.', ' ' ) ); ?></td>
						<td></td>
						<td class="text-right"><?php echo esc_html( number_format( $document->getTaxTotal(), 2, '.', ' ' ) ); ?></td>
						<td class="text-right"><?php echo esc_html( number_format( $document->getTotal(), 2, '.', ' ' ) ); ?></td>
					</tr>
				</tfoot>
				<?php endif; ?>
			</table>
		</div>

		<!-- Invoice Total Bar -->
		<div class="document-total-bar">
			<span class="total-label"><?php esc_html_e( 'INVOICE TOTAL:', 'ihumbak-invoices' ); ?></span>
			<span class="total-value"><?php echo esc_html( number_format( $document->getTotal(), 2, '.', ' ' ) . ' ' . $currency ); ?></span>
		</div>

		<!-- Notes -->
		<?php if ( $document->getNotes() ) : ?>
			<div class="notes-section">
				<div class="notes-title"><?php esc_html_e( 'Notes', 'ihumbak-invoices' ); ?></div>
				<div class="notes-content"><?php echo nl2br( esc_html( $document->getNotes() ) ); ?></div>
			</div>
		<?php endif; ?>

		<!-- Footer -->
		<div class="document-footer">
			<?php if ( ! empty( $settings['pdf']['footer_text'] ) ) : ?>
				<?php echo esc_html( $settings['pdf']['footer_text'] ); ?>
			<?php else : ?>
				<?php esc_html_e( 'This invoice was generated electronically and is valid without a signature.', 'ihumbak-invoices' ); ?>
			<?php endif; ?>
		</div>
	</div>
</body>
</html>
