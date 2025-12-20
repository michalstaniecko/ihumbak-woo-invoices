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
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>Invoice <?php echo esc_html( $document->getDocumentNumber() ); ?></title>
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
							<?php echo esc_html( $seller->getAddress() ); ?><br>
							<?php echo esc_html( $seller->getPostcode() . ' ' . $seller->getCity() ); ?><br>
							<?php echo esc_html( $seller->getCountry() ); ?>
							<?php if ( $seller->getNip() ) : ?>
								<div class="tax-id">VAT ID: <?php echo esc_html( $seller->getNip() ); ?></div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<!-- Row 2: Centered Document Title -->
		<div class="document-title-section">
			<div class="document-title">VAT Invoice</div>
			<div class="document-number"><?php echo esc_html( $document->getDocumentNumber() ); ?></div>
		</div>

		<!-- Row 3: Invoice Details + Buyer Data -->
		<table class="details-section" cellpadding="0" cellspacing="0">
			<tr>
				<td class="details-left">
					<div class="detail-box">
						<div class="detail-box-title">Invoice Details</div>
						<div class="detail-row">
							<span class="label">Invoice No:</span>
							<span class="value"><?php echo esc_html( $document->getDocumentNumber() ); ?></span>
						</div>
						<div class="detail-row">
							<span class="label">Issue Date:</span>
							<span class="value"><?php echo $document->getIssueDate() ? esc_html( $document->getIssueDate()->format( 'Y-m-d' ) ) : '-'; ?></span>
						</div>
						<div class="detail-row">
							<span class="label">Sale Date:</span>
							<span class="value"><?php echo $document->getSaleDate() ? esc_html( $document->getSaleDate()->format( 'Y-m-d' ) ) : '-'; ?></span>
						</div>
						<?php if ( $document->getDueDate() ) : ?>
							<div class="detail-row">
								<span class="label">Due Date:</span>
								<span class="value"><?php echo esc_html( $document->getDueDate()->format( 'Y-m-d' ) ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $document->getOrderId() ) : ?>
							<div class="detail-row">
								<span class="label">Order No:</span>
								<span class="value">#<?php echo esc_html( $document->getOrderId() ); ?></span>
							</div>
						<?php endif; ?>
						<?php
						$payment_methods = array(
							'transfer' => 'Bank Transfer',
							'cash'     => 'Cash',
							'card'     => 'Card',
							'online'   => 'Online Payment',
						);
						$method          = $document->getPaymentMethod();
						if ( $method ) :
							?>
							<div class="detail-row">
								<span class="label">Payment:</span>
								<span class="value"><?php echo esc_html( $payment_methods[ $method ] ?? ucfirst( $method ) ); ?></span>
							</div>
						<?php endif; ?>
					</div>
				</td>
				<td class="details-right">
					<div class="detail-box">
						<div class="detail-box-title">Buyer</div>
						<?php if ( $buyer ) : ?>
							<div class="party-name"><?php echo esc_html( $buyer->getName() ); ?></div>
							<div class="party-address">
								<?php echo esc_html( $buyer->getAddress() ); ?><br>
								<?php echo esc_html( $buyer->getPostcode() . ' ' . $buyer->getCity() ); ?><br>
								<?php echo esc_html( $buyer->getCountry() ); ?>
							</div>
							<?php if ( $buyer->getNip() ) : ?>
								<div class="party-tax-id">VAT ID: <?php echo esc_html( $buyer->getNip() ); ?></div>
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
						<th class="col-no text-center">#</th>
						<th class="col-name">Description</th>
						<th class="col-qty text-right">Qty</th>
						<th class="col-unit text-center">Unit</th>
						<th class="col-price text-right">Unit Price</th>
						<th class="col-tax-rate text-center">VAT %</th>
						<th class="col-tax text-right">VAT Amount</th>
						<th class="col-total text-right">Total Gross</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $items as $index => $item ) : ?>
						<tr>
							<td class="text-center"><?php echo esc_html( $index + 1 ); ?></td>
							<td class="item-name"><?php echo esc_html( $item->getName() ); ?></td>
							<td class="text-right"><?php echo esc_html( number_format( $item->getQuantity(), 2, '.', '' ) ); ?></td>
							<td class="text-center"><?php echo esc_html( $item->getUnit() ); ?></td>
							<td class="text-right"><?php echo esc_html( number_format( $item->getUnitPriceNet(), 2, '.', ' ' ) ); ?></td>
							<td class="text-center"><?php echo esc_html( number_format( $item->getTaxRate(), 0 ) ); ?>%</td>
							<td class="text-right"><?php echo esc_html( number_format( $item->getTaxAmount(), 2, '.', ' ' ) ); ?></td>
							<td class="text-right"><?php echo esc_html( number_format( $item->getLineTotalGross(), 2, '.', ' ' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<!-- VAT Summary and Totals -->
		<table class="vat-summary" cellpadding="0" cellspacing="0">
			<tr>
				<td class="vat-summary-left">
					<?php if ( ! empty( $vat_breakdown ) ) : ?>
						<table class="vat-table" cellpadding="0" cellspacing="0">
							<thead>
								<tr>
									<th>VAT Rate</th>
									<th class="text-right">Net Amount</th>
									<th class="text-right">VAT Amount</th>
									<th class="text-right">Gross Amount</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $vat_breakdown as $rate => $values ) : ?>
									<tr>
										<td><?php echo esc_html( number_format( $values['rate'], 0 ) ); ?>%</td>
										<td class="text-right"><?php echo esc_html( number_format( $values['net'], 2, '.', ' ' ) ); ?></td>
										<td class="text-right"><?php echo esc_html( number_format( $values['tax'], 2, '.', ' ' ) ); ?></td>
										<td class="text-right"><?php echo esc_html( number_format( $values['gross'], 2, '.', ' ' ) ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</td>
				<td class="vat-summary-right">
					<div class="totals-section">
						<table class="totals-table" cellpadding="0" cellspacing="0">
							<tr>
								<td class="label">Subtotal (Net):</td>
								<td class="value"><?php echo esc_html( number_format( $document->getSubtotal(), 2, '.', ' ' ) . ' ' . $currency ); ?></td>
							</tr>
							<tr>
								<td class="label">VAT Total:</td>
								<td class="value"><?php echo esc_html( number_format( $document->getTaxTotal(), 2, '.', ' ' ) . ' ' . $currency ); ?></td>
							</tr>
							<tr class="total-row">
								<td class="label">TOTAL (Gross):</td>
								<td class="value"><?php echo esc_html( number_format( $document->getTotal(), 2, '.', ' ' ) . ' ' . $currency ); ?></td>
							</tr>
						</table>
					</div>
				</td>
			</tr>
		</table>

		<!-- Payment Information -->
		<?php if ( $seller && ( $seller->getBankName() || $seller->getBankAccount() ) ) : ?>
			<div class="payment-section">
				<div class="payment-title">Payment Information</div>
				<div class="payment-details">
					<?php if ( $seller->getBankName() ) : ?>
						<div>
							<span class="label">Bank:</span>
							<span class="value"><?php echo esc_html( $seller->getBankName() ); ?></span>
						</div>
					<?php endif; ?>
					<?php if ( $seller->getBankAccount() ) : ?>
						<div>
							<span class="label">Account Number:</span>
							<span class="value"><?php echo esc_html( $seller->getBankAccount() ); ?></span>
						</div>
					<?php endif; ?>
					<div>
						<span class="label">Amount Due:</span>
						<span class="value"><?php echo esc_html( number_format( $document->getTotal(), 2, '.', ' ' ) . ' ' . $currency ); ?></span>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<!-- Notes -->
		<?php if ( $document->getNotes() ) : ?>
			<div class="notes-section">
				<div class="notes-title">Notes</div>
				<div class="notes-content"><?php echo nl2br( esc_html( $document->getNotes() ) ); ?></div>
			</div>
		<?php endif; ?>

		<!-- Footer -->
		<div class="document-footer">
			<?php if ( ! empty( $settings['pdf']['footer_text'] ) ) : ?>
				<?php echo esc_html( $settings['pdf']['footer_text'] ); ?>
			<?php else : ?>
				This invoice was generated electronically and is valid without a signature.
			<?php endif; ?>
		</div>
	</div>
</body>
</html>
