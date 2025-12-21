<?php
/**
 * Order invoices metabox template.
 *
 * @package IHumbak\Invoices
 *
 * @var int                                   $order_id            Order ID.
 * @var \IHumbak\Invoices\Models\Document[]  $documents           Documents for this order.
 * @var string                                $create_invoice_url  URL to create invoice.
 * @var string                                $create_receipt_url  URL to create receipt.
 * @var array<int, string>                    $edit_urls           Edit URLs keyed by document ID.
 * @var array<int, string>                    $pdf_urls            PDF URLs keyed by document ID.
 * @var array<int, string>                    $credit_note_urls    Credit note URLs keyed by invoice ID.
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="ihumbak-order-metabox">
	<?php if ( empty( $documents ) ) : ?>
		<p class="ihumbak-no-documents">
			<?php esc_html_e( 'No documents for this order.', 'ihumbak-invoices' ); ?>
		</p>
	<?php else : ?>
		<ul class="ihumbak-documents-list">
			<?php foreach ( $documents as $document ) : ?>
				<?php
				$doc_id     = $document->getId();
				$doc_number = $document->getDocumentNumber();
				$is_draft   = $document->isDraft();
				?>
				<li class="ihumbak-document-item">
					<span class="ihumbak-doc-type"><?php echo esc_html( $document->getDocumentTypeLabel() ); ?></span>
					<span class="ihumbak-doc-number">
						<a href="<?php echo esc_url( $edit_urls[ $doc_id ] ); ?>">
							<?php echo esc_html( $doc_number ?: __( '(Draft)', 'ihumbak-invoices' ) ); ?>
						</a>
					</span>
					<span class="ihumbak-status ihumbak-status-<?php echo esc_attr( $document->getStatus() ); ?>">
						<?php echo esc_html( $document->getStatusLabel() ); ?>
					</span>
					<?php if ( ! $is_draft ) : ?>
						<a href="<?php echo esc_url( $pdf_urls[ $doc_id ] ); ?>"
						   class="ihumbak-pdf-link"
						   target="_blank"
						   title="<?php esc_attr_e( 'Download PDF', 'ihumbak-invoices' ); ?>">
							<span class="dashicons dashicons-pdf"></span>
						</a>
						<?php if ( isset( $credit_note_urls[ $doc_id ] ) ) : ?>
							<a href="<?php echo esc_url( $credit_note_urls[ $doc_id ] ); ?>"
							   class="ihumbak-credit-note-link"
							   title="<?php esc_attr_e( 'Create Credit Note', 'ihumbak-invoices' ); ?>">
								<span class="dashicons dashicons-undo"></span>
							</a>
						<?php endif; ?>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<div class="ihumbak-metabox-actions">
		<a href="<?php echo esc_url( $create_invoice_url ); ?>" class="button">
			<?php esc_html_e( 'Create Invoice', 'ihumbak-invoices' ); ?>
		</a>
		<a href="<?php echo esc_url( $create_receipt_url ); ?>" class="button">
			<?php esc_html_e( 'Create Receipt', 'ihumbak-invoices' ); ?>
		</a>
	</div>
</div>
