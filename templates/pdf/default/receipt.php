<?php
/**
 * Default Receipt PDF Template
 *
 * EU Standard Receipt Template - English
 * Simplified document for individual customers
 *
 * Available variables:
 * @var IHumbak\Invoices\Models\Receipt $document
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
    <title>Receipt <?php echo esc_html( $document->getDocumentNumber() ); ?></title>
    <style>
        <?php echo $styles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </style>
</head>
<body>
    <div class="document-container">
        <!-- Header -->
        <div class="document-header">
            <table class="header-top" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width: 50%;">
                        <?php if ( $logo_url ) : ?>
                            <img src="<?php echo esc_attr( $logo_url ); ?>" alt="Logo" class="logo">
                        <?php elseif ( $seller ) : ?>
                            <div class="party-name"><?php echo esc_html( $seller->getName() ); ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="width: 50%;">
                        <div class="document-title">RECEIPT</div>
                        <div class="document-number"><?php echo esc_html( $document->getDocumentNumber() ); ?></div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Parties: Seller and Buyer -->
        <table class="parties-section" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding-right: 10pt;">
                    <div class="party-box">
                        <div class="party-label">Seller</div>
                        <?php if ( $seller ) : ?>
                            <div class="party-name"><?php echo esc_html( $seller->getName() ); ?></div>
                            <div class="party-address">
                                <?php echo esc_html( $seller->getAddress() ); ?><br>
                                <?php echo esc_html( $seller->getPostcode() . ' ' . $seller->getCity() ); ?><br>
                                <?php echo esc_html( $seller->getCountry() ); ?>
                            </div>
                            <?php if ( $seller->getNip() ) : ?>
                                <div class="party-tax-id">
                                    <strong>VAT ID:</strong> <?php echo esc_html( $seller->getNip() ); ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </td>
                <td style="padding-left: 10pt;">
                    <div class="party-box">
                        <div class="party-label">Buyer</div>
                        <?php if ( $buyer ) : ?>
                            <div class="party-name"><?php echo esc_html( $buyer->getName() ); ?></div>
                            <?php if ( $buyer->getAddress() ) : ?>
                                <div class="party-address">
                                    <?php echo esc_html( $buyer->getAddress() ); ?><br>
                                    <?php if ( $buyer->getPostcode() || $buyer->getCity() ) : ?>
                                        <?php echo esc_html( $buyer->getPostcode() . ' ' . $buyer->getCity() ); ?><br>
                                    <?php endif; ?>
                                    <?php echo esc_html( $buyer->getCountry() ); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ( $buyer->getNip() ) : ?>
                                <div class="party-tax-id">
                                    <strong>VAT ID:</strong> <?php echo esc_html( $buyer->getNip() ); ?>
                                </div>
                            <?php endif; ?>
                        <?php else : ?>
                            <div class="party-address text-muted">Individual Customer</div>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Document Details -->
        <table class="document-details" cellpadding="0" cellspacing="0">
            <tr>
                <td class="label">Issue Date:</td>
                <td class="value">
                    <?php echo $document->getIssueDate() ? esc_html( $document->getIssueDate()->format( 'Y-m-d' ) ) : '-'; ?>
                </td>
                <td class="label">Sale Date:</td>
                <td class="value">
                    <?php echo $document->getSaleDate() ? esc_html( $document->getSaleDate()->format( 'Y-m-d' ) ) : '-'; ?>
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
                This receipt was generated electronically and is valid without a signature.
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
