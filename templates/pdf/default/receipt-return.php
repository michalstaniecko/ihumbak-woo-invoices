<?php
/**
 * Default Receipt Return PDF Template
 *
 * Receipt Return Template - Informational Document
 *
 * Available variables:
 *
 * @var IHumbak\Invoices\Models\ReceiptReturn $document
 * @var IHumbak\Invoices\Models\Document|null $original_document Original receipt being returned.
 * @var IHumbak\Invoices\Models\DocumentItem[] $original_items Original receipt items.
 * @var IHumbak\Invoices\Models\Seller|null $seller
 * @var IHumbak\Invoices\Models\Buyer|null $buyer
 * @var IHumbak\Invoices\Models\DocumentItem[] $items Returned items (negative values).
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
	<title><?php echo esc_html( sprintf( __( 'Receipt Return %s', 'ihumbak-invoices' ), $document->getDocumentNumber() ) ); ?></title>
	<style>
		<?php echo $styles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</style>
</head>
<body class="document-type-receipt-return">
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
			<div class="document-title"><?php echo esc_html( strtoupper( __( 'Receipt Return', 'ihumbak-invoices' ) ) ); ?></div>
			<div class="document-number"><?php echo esc_html( $document->getDocumentNumber() ); ?></div>
		</div>

		<!-- Correction Reference Box -->
		<?php if ( $document->isManualEntry() ) : ?>
		<!-- Manual entry mode: show data from receipt return fields -->
		<div class="detail-box correction-details">
			<div class="detail-box-title"><?php esc_html_e( 'Corrects Receipt', 'ihumbak-invoices' ); ?></div>
			<div class="detail-row">
				<span class="label"><?php esc_html_e( 'Original Receipt No:', 'ihumbak-invoices' ); ?></span>
				<span class="value"><?php echo esc_html( $document->getOriginalDocumentNumber() ); ?></span>
			</div>
			<?php if ( $document->getOriginalDocumentDate() ) : ?>
			<div class="detail-row">
				<span class="label"><?php esc_html_e( 'Original Issue Date:', 'ihumbak-invoices' ); ?></span>
				<span class="value"><?php echo esc_html( $document->getOriginalDocumentDate()->format( 'Y-m-d' ) ); ?></span>
			</div>
			<?php endif; ?>
		</div>
		<?php elseif ( $original_document ) : ?>
		<!-- System mode: show data from original document -->
		<div class="detail-box correction-details">
			<div class="detail-box-title"><?php esc_html_e( 'Corrects Receipt', 'ihumbak-invoices' ); ?></div>
			<div class="detail-row">
				<span class="label"><?php esc_html_e( 'Original Receipt No:', 'ihumbak-invoices' ); ?></span>
				<span class="value"><?php echo esc_html( $original_document->getDocumentNumber() ); ?></span>
			</div>
			<div class="detail-row">
				<span class="label"><?php esc_html_e( 'Original Issue Date:', 'ihumbak-invoices' ); ?></span>
				<span class="value"><?php echo $original_document->getIssueDate() ? esc_html( $original_document->getIssueDate()->format( 'Y-m-d' ) ) : '-'; ?></span>
			</div>
			<div class="detail-row">
				<span class="label"><?php esc_html_e( 'Original Total:', 'ihumbak-invoices' ); ?></span>
				<span class="value"><?php echo esc_html( number_format( $original_document->getTotal(), 2, '.', ' ' ) . ' ' . $original_document->getCurrency() ); ?></span>
			</div>
		</div>
		<?php endif; ?>

		<!-- Row 3: Receipt Return Details + Buyer Data -->
		<table class="details-section" cellpadding="0" cellspacing="0">
			<tr>
				<td class="details-left">
					<div class="detail-box">
						<div class="detail-box-title"><?php esc_html_e( 'Receipt Return Details', 'ihumbak-invoices' ); ?></div>
						<div class="detail-row">
							<span class="label"><?php esc_html_e( 'Receipt Return No:', 'ihumbak-invoices' ); ?></span>
							<span class="value"><?php echo esc_html( $document->getDocumentNumber() ); ?></span>
						</div>
						<div class="detail-row">
							<span class="label"><?php esc_html_e( 'Issue Date:', 'ihumbak-invoices' ); ?></span>
							<span class="value"><?php echo $document->getIssueDate() ? esc_html( $document->getIssueDate()->format( 'Y-m-d' ) ) : '-'; ?></span>
						</div>
						<div class="detail-row">
							<span class="label"><?php esc_html_e( 'Return Type:', 'ihumbak-invoices' ); ?></span>
							<span class="value">
								<?php echo $document->isFullCorrection() ? esc_html__( 'Full', 'ihumbak-invoices' ) : esc_html__( 'Partial', 'ihumbak-invoices' ); ?>
							</span>
						</div>
						<div class="detail-row">
							<span class="label"><?php esc_html_e( 'Refund Amount:', 'ihumbak-invoices' ); ?></span>
							<span class="value"><?php echo esc_html( number_format( abs( $document->getTotal() ), 2, '.', ' ' ) . ' ' . $currency ); ?></span>
						</div>
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

		<!-- Return Reason -->
		<?php if ( $document->getCorrectionReason() ) : ?>
		<div class="detail-box correction-reason">
			<div class="detail-box-title"><?php esc_html_e( 'Reason for Return', 'ihumbak-invoices' ); ?></div>
			<div class="detail-row">
				<span class="value"><?php echo nl2br( esc_html( $document->getCorrectionReason() ) ); ?></span>
			</div>
		</div>
		<?php endif; ?>

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
							<td class="text-right"><?php echo esc_html( rtrim( rtrim( number_format( $item->getQuantity(), 2, '.', '' ), '0' ), '.' ) ); ?></td>
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

		<!-- Receipt Return Total Bar -->
		<div class="document-total-bar">
			<span class="total-label"><?php esc_html_e( 'REFUND AMOUNT:', 'ihumbak-invoices' ); ?></span>
			<span class="total-value"><?php echo esc_html( number_format( abs( $document->getTotal() ), 2, '.', ' ' ) . ' ' . $currency ); ?></span>
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
				<?php esc_html_e( 'This receipt return was generated electronically and is valid without a signature.', 'ihumbak-invoices' ); ?>
			<?php endif; ?>
		</div>
	</div>
</body>
</html>
