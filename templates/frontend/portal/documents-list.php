<?php
/**
 * Customer portal - Documents list.
 *
 * This template displays the list of all customer documents in My Account.
 *
 * @package IHumbak\Invoices
 *
 * @var \IHumbak\Invoices\Models\Document[] $documents     Customer documents.
 * @var array<int, string>                  $download_urls Download URLs keyed by document ID.
 * @var array<int, string>                  $order_urls    Order URLs keyed by order ID.
 */

defined( 'ABSPATH' ) || exit;
?>

<?php if ( empty( $documents ) ) : ?>
	<div class="woocommerce-invoices-empty">
		<p><?php esc_html_e( 'You do not have any invoices yet.', 'ihumbak-invoices' ); ?></p>
	</div>
<?php else : ?>
	<table class="woocommerce-invoices-table shop_table shop_table_responsive">
		<thead>
			<tr>
				<th class="invoice-number"><?php esc_html_e( 'Number', 'ihumbak-invoices' ); ?></th>
				<th class="invoice-type"><?php esc_html_e( 'Type', 'ihumbak-invoices' ); ?></th>
				<th class="invoice-date"><?php esc_html_e( 'Date', 'ihumbak-invoices' ); ?></th>
				<th class="invoice-order"><?php esc_html_e( 'Order', 'ihumbak-invoices' ); ?></th>
				<th class="invoice-total"><?php esc_html_e( 'Total', 'ihumbak-invoices' ); ?></th>
				<th class="invoice-actions"><?php esc_html_e( 'Actions', 'ihumbak-invoices' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $documents as $document ) : ?>
				<?php
				$doc_id   = $document->getId();
				$order_id = $document->getOrderId();
				?>
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
					<td class="invoice-order" data-title="<?php esc_attr_e( 'Order', 'ihumbak-invoices' ); ?>">
						<?php if ( $order_id && isset( $order_urls[ $order_id ] ) ) : ?>
							<a href="<?php echo esc_url( $order_urls[ $order_id ] ); ?>">
								#<?php echo esc_html( (string) $order_id ); ?>
							</a>
						<?php else : ?>
							&mdash;
						<?php endif; ?>
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
<?php endif; ?>
