<?php
/**
 * Customer portal - Order documents section.
 *
 * This template displays documents associated with a specific order
 * on the order details page in My Account.
 *
 * @package IHumbak\Invoices
 *
 * @var \IHumbak\Invoices\Models\Document[] $documents     Order documents.
 * @var array<int, string>                  $download_urls Download URLs keyed by document ID.
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $documents ) ) {
	return;
}
?>

<section class="woocommerce-order-documents">
	<h2><?php esc_html_e( 'Invoices', 'ihumbak-invoices' ); ?></h2>

	<table class="woocommerce-invoices-table shop_table shop_table_responsive">
		<thead>
			<tr>
				<th class="invoice-number"><?php esc_html_e( 'Number', 'ihumbak-invoices' ); ?></th>
				<th class="invoice-type"><?php esc_html_e( 'Type', 'ihumbak-invoices' ); ?></th>
				<th class="invoice-date"><?php esc_html_e( 'Date', 'ihumbak-invoices' ); ?></th>
				<th class="invoice-total"><?php esc_html_e( 'Total', 'ihumbak-invoices' ); ?></th>
				<th class="invoice-actions"><?php esc_html_e( 'Actions', 'ihumbak-invoices' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $documents as $document ) : ?>
				<?php $doc_id = $document->getId(); ?>
				<tr>
					<td class="invoice-number" data-title="<?php esc_attr_e( 'Number', 'ihumbak-invoices' ); ?>">
						<?php echo esc_html( $document->getDocumentNumber() ); ?>
					</td>
					<td class="invoice-type" data-title="<?php esc_attr_e( 'Type', 'ihumbak-invoices' ); ?>">
						<?php echo esc_html( $document->getDocumentTypeLabel() ); ?>
					</td>
					<td class="invoice-date" data-title="<?php esc_attr_e( 'Date', 'ihumbak-invoices' ); ?>">
						<?php
						$issue_date = $document->getIssueDate();
						echo $issue_date ? esc_html( $issue_date->format( 'Y-m-d' ) ) : '&mdash;';
						?>
					</td>
					<td class="invoice-total" data-title="<?php esc_attr_e( 'Total', 'ihumbak-invoices' ); ?>">
						<?php
						echo esc_html(
							number_format( $document->getTotal(), 2, '.', ' ' ) . ' ' . $document->getCurrency()
						);
						?>
					</td>
					<td class="invoice-actions" data-title="<?php esc_attr_e( 'Actions', 'ihumbak-invoices' ); ?>">
						<a href="<?php echo esc_url( $download_urls[ $doc_id ] ); ?>"
							class="button invoice-download-btn"
							target="_blank">
							<span class="dashicons dashicons-pdf"></span>
							<?php esc_html_e( 'Download', 'ihumbak-invoices' ); ?>
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</section>
