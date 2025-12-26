<?php
/**
 * Credit Note edit template.
 *
 * @package IHumbak\Invoices
 *
 * @var \IHumbak\Invoices\Models\CreditNote|null            $document                  Document being edited (null for new).
 * @var \IHumbak\Invoices\Models\Document|null              $original_document         Original invoice being corrected.
 * @var array<string, string>                               $seller                    Seller data.
 * @var array<string, string>                               $buyer                     Buyer data.
 * @var array<int, array<string, mixed>>                    $items                     Document items.
 * @var array<int, \IHumbak\Invoices\Models\DocumentItem>   $original_items            Original invoice items.
 * @var \IHumbak\Invoices\Models\Document[]                 $available_invoices        Invoices available for correction.
 * @var array<int, \IHumbak\Invoices\Models\CreditNote[]>   $existing_corrections      Map of invoice ID to credit notes.
 * @var array<int, array<string, mixed>>                    $available_refunds         WC refunds for the order.
 * @var string                                              $next_number               Preview of next document number.
 * @var int|null                                            $pre_selected_invoice_id   Pre-selected invoice ID from URL.
 * @var \IHumbak\Invoices\Modules\Invoice\SuperAdminService $super_admin_service       Super admin service.
 */

defined( 'ABSPATH' ) || exit;

use IHumbak\Invoices\Models\CreditNote;

$is_new     = ! $document || ! $document->getId();
$is_draft   = ! $document || $document->isDraft();
$can_edit   = $is_new || $is_draft;
$page_title = $is_new
	? __( 'New Credit Note', 'ihumbak-invoices' )
	: sprintf(
		/* translators: %s: Document number */
		__( 'Edit Credit Note: %s', 'ihumbak-invoices' ),
		$document->getDocumentNumber()
	);
?>

<div class="wrap ihumbak-document-edit-wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html( $page_title ); ?></h1>

	<a href="<?php echo esc_url( admin_url( 'admin.php?page=ihumbak-invoices' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Back to List', 'ihumbak-invoices' ); ?>
	</a>

	<hr class="wp-header-end">

	<?php
	// Display messages.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';

	if ( 'saved' === $message ) :
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Credit Note saved successfully.', 'ihumbak-invoices' ); ?></p>
		</div>
		<?php
	elseif ( 'reverted' === $message ) :
		?>
		<div class="notice notice-warning is-dismissible">
			<p><?php esc_html_e( 'Document status reverted to draft. You can now edit the document.', 'ihumbak-invoices' ); ?></p>
		</div>
		<?php
	elseif ( 'error' === $message ) :
		$error_message = get_transient( 'ihumbak_save_error_' . get_current_user_id() );
		delete_transient( 'ihumbak_save_error_' . get_current_user_id() );
		?>
		<div class="notice notice-error is-dismissible">
			<p>
				<?php esc_html_e( 'Error saving Credit Note.', 'ihumbak-invoices' ); ?>
				<?php if ( $error_message ) : ?>
					<br><small><?php echo esc_html( $error_message ); ?></small>
				<?php endif; ?>
			</p>
		</div>
		<?php
	endif;
	?>

	<?php if ( ! $can_edit ) : ?>
		<div class="notice notice-warning">
			<p><?php esc_html_e( 'This Credit Note has been issued and cannot be edited.', 'ihumbak-invoices' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="ihumbak-document-form">
		<input type="hidden" name="action" value="ihumbak_save_credit_note">
		<input type="hidden" name="document_type" value="credit_note">
		<?php wp_nonce_field( 'ihumbak_save_document', 'ihumbak_nonce' ); ?>

		<?php if ( $document && $document->getId() ) : ?>
			<input type="hidden" name="document_id" value="<?php echo esc_attr( $document->getId() ); ?>">
		<?php endif; ?>

		<div class="ihumbak-document-columns">
			<!-- Main column -->
			<div class="ihumbak-document-main">

				<!-- Source Invoice Selection -->
				<div class="ihumbak-card ihumbak-source-invoice-card">
					<h3><?php esc_html_e( 'Source Invoice', 'ihumbak-invoices' ); ?></h3>

					<table class="form-table">
						<tr>
							<th>
								<label for="corrected_document_id">
									<?php esc_html_e( 'Select Invoice to Correct', 'ihumbak-invoices' ); ?>
									<span class="required">*</span>
								</label>
							</th>
							<td>
								<select id="corrected_document_id" name="corrected_document_id"
										<?php disabled( ! $can_edit ); ?>
										<?php echo $can_edit ? 'required' : ''; ?>>
									<option value=""><?php esc_html_e( '-- Select Invoice --', 'ihumbak-invoices' ); ?></option>
									<?php foreach ( $available_invoices as $inv ) : ?>
										<?php
										$selected = false;
										if ( $document && $document->getCorrectedDocumentId() === $inv->getId() ) {
											$selected = true;
										} elseif ( $pre_selected_invoice_id && $pre_selected_invoice_id === $inv->getId() ) {
											$selected = true;
										}
										$has_corrections = isset( $existing_corrections[ $inv->getId() ] );
										$label           = $inv->getDocumentNumber() . ' - ' .
											( $inv->getIssueDate() ? $inv->getIssueDate()->format( 'Y-m-d' ) : '' ) .
											' (' . number_format( $inv->getTotal(), 2 ) . ' ' . $inv->getCurrency() . ')';
										if ( $has_corrections ) {
											$count  = count( $existing_corrections[ $inv->getId() ] );
											$label .= ' ' . sprintf(
												/* translators: %d: Number of existing corrections */
												_n( '[%d correction]', '[%d corrections]', $count, 'ihumbak-invoices' ),
												$count
											);
										}
										?>
										<option value="<?php echo esc_attr( $inv->getId() ); ?>"
												data-has-corrections="<?php echo $has_corrections ? '1' : '0'; ?>"
												<?php selected( $selected ); ?>>
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<?php if ( $can_edit ) : ?>
									<button type="button" id="ihumbak-load-invoice" class="button">
										<span class="dashicons dashicons-download" style="vertical-align: middle; margin-top: -2px;"></span>
										<?php esc_html_e( 'Load Invoice Data', 'ihumbak-invoices' ); ?>
									</button>
									<span id="ihumbak-load-status" class="spinner" style="float: none; margin: 4px 0 0 0;"></span>
								<?php endif; ?>
								<p id="correction-warning" class="description" style="color: #d63638; display: none;">
									<span class="dashicons dashicons-warning" style="vertical-align: middle;"></span>
									<?php esc_html_e( 'This invoice already has existing credit notes. Creating another correction may result in over-correction.', 'ihumbak-invoices' ); ?>
								</p>
							</td>
						</tr>
						<?php if ( $original_document ) : ?>
						<tr id="original-invoice-info">
							<th><?php esc_html_e( 'Original Invoice', 'ihumbak-invoices' ); ?></th>
							<td>
								<div id="original-invoice-details">
									<strong><?php echo esc_html( $original_document->getDocumentNumber() ); ?></strong><br>
									<?php esc_html_e( 'Date:', 'ihumbak-invoices' ); ?>
									<?php echo esc_html( $original_document->getIssueDate() ? $original_document->getIssueDate()->format( 'Y-m-d' ) : '-' ); ?><br>
									<?php esc_html_e( 'Total:', 'ihumbak-invoices' ); ?>
									<?php echo esc_html( number_format( $original_document->getTotal(), 2 ) . ' ' . $original_document->getCurrency() ); ?>
								</div>
							</td>
						</tr>
						<?php endif; ?>
					</table>
				</div>

				<!-- Correction Details -->
				<div class="ihumbak-card">
					<h3><?php esc_html_e( 'Correction Details', 'ihumbak-invoices' ); ?></h3>

					<table class="form-table">
						<tr>
							<th><label><?php esc_html_e( 'Correction Type', 'ihumbak-invoices' ); ?></label></th>
							<td>
								<?php
								$current_type = $document ? $document->getCorrectionType() : CreditNote::CORRECTION_TYPE_PARTIAL;
								?>
								<label style="margin-right: 20px;">
									<input type="radio" name="correction_type" value="partial"
										   <?php checked( $current_type, CreditNote::CORRECTION_TYPE_PARTIAL ); ?>
										   <?php disabled( ! $can_edit ); ?>>
									<?php esc_html_e( 'Partial Correction', 'ihumbak-invoices' ); ?>
								</label>
								<label>
									<input type="radio" name="correction_type" value="full"
										   <?php checked( $current_type, CreditNote::CORRECTION_TYPE_FULL ); ?>
										   <?php disabled( ! $can_edit ); ?>>
									<?php esc_html_e( 'Full Correction (Cancel Entire Invoice)', 'ihumbak-invoices' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th>
								<label for="correction_reason">
									<?php esc_html_e( 'Correction Reason', 'ihumbak-invoices' ); ?>
									<span class="required">*</span>
								</label>
							</th>
							<td>
								<textarea id="correction_reason" name="correction_reason" rows="3"
										  class="large-text" required <?php disabled( ! $can_edit ); ?>><?php
									echo esc_textarea( $document ? $document->getCorrectionReason() : '' );
								?></textarea>
							</td>
						</tr>
						<?php if ( ! empty( $available_refunds ) ) : ?>
						<tr id="refund-selection-row">
							<th><label for="refund_id"><?php esc_html_e( 'Link to WC Refund (Optional)', 'ihumbak-invoices' ); ?></label></th>
							<td>
								<select id="refund_id" name="refund_id" <?php disabled( ! $can_edit ); ?>>
									<option value=""><?php esc_html_e( '-- No Refund --', 'ihumbak-invoices' ); ?></option>
									<?php foreach ( $available_refunds as $refund ) : ?>
										<option value="<?php echo esc_attr( $refund['id'] ); ?>"
												<?php selected( $document ? $document->getRefundId() : null, $refund['id'] ); ?>>
											#<?php echo esc_html( $refund['id'] ); ?> -
											<?php echo esc_html( number_format( $refund['amount'], 2 ) ); ?> -
											<?php echo esc_html( $refund['date'] ); ?>
											<?php if ( $refund['reason'] ) : ?>
												(<?php echo esc_html( substr( $refund['reason'], 0, 30 ) ); ?>)
											<?php endif; ?>
										</option>
									<?php endforeach; ?>
								</select>
								<?php if ( $can_edit ) : ?>
									<button type="button" id="ihumbak-load-refund" class="button">
										<?php esc_html_e( 'Apply Refund Data', 'ihumbak-invoices' ); ?>
									</button>
								<?php endif; ?>
							</td>
						</tr>
						<?php endif; ?>
					</table>
				</div>

				<!-- Credit Note Details -->
				<div class="ihumbak-card">
					<h3><?php esc_html_e( 'Credit Note Details', 'ihumbak-invoices' ); ?></h3>

					<table class="form-table">
						<tr>
							<th><label for="document_number"><?php esc_html_e( 'Credit Note Number', 'ihumbak-invoices' ); ?></label></th>
							<td>
								<?php if ( $is_new ) : ?>
									<input type="text" id="document_number" name="document_number"
										   value="<?php echo esc_attr( $next_number ); ?>"
										   class="regular-text" readonly>
									<p class="description"><?php esc_html_e( 'Number will be assigned automatically upon save.', 'ihumbak-invoices' ); ?></p>
								<?php else : ?>
									<input type="text" id="document_number"
										   value="<?php echo esc_attr( $document->getDocumentNumber() ); ?>"
										   class="regular-text" readonly>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th><label for="issue_date"><?php esc_html_e( 'Issue Date', 'ihumbak-invoices' ); ?> <span class="required">*</span></label></th>
							<td>
								<input type="date" id="issue_date" name="issue_date"
									   value="<?php echo esc_attr( $document ? $document->getIssueDate()?->format( 'Y-m-d' ) : gmdate( 'Y-m-d' ) ); ?>"
									   required <?php disabled( ! $can_edit ); ?>>
							</td>
						</tr>
						<tr>
							<th><label for="sale_date"><?php esc_html_e( 'Sale Date', 'ihumbak-invoices' ); ?> <span class="required">*</span></label></th>
							<td>
								<input type="date" id="sale_date" name="sale_date"
									   value="<?php echo esc_attr( $document ? $document->getSaleDate()?->format( 'Y-m-d' ) : gmdate( 'Y-m-d' ) ); ?>"
									   required <?php disabled( ! $can_edit ); ?>>
							</td>
						</tr>
					</table>
				</div>

				<!-- Items -->
				<?php
				$require_nip             = true;
				$allow_negative_quantity = true;
				include IHUMBAK_INVOICES_PATH . 'templates/admin/partials/items-table.php';
				?>

				<!-- Notes -->
				<div class="ihumbak-card">
					<h3><?php esc_html_e( 'Notes', 'ihumbak-invoices' ); ?></h3>
					<textarea name="notes" rows="3" class="large-text" <?php disabled( ! $can_edit ); ?>><?php
						echo esc_textarea( $document ? $document->getNotes() : '' );
					?></textarea>
				</div>
			</div>

			<!-- Sidebar -->
			<div class="ihumbak-document-sidebar">

				<!-- Actions -->
				<div class="ihumbak-card ihumbak-actions-card">
					<h3><?php esc_html_e( 'Actions', 'ihumbak-invoices' ); ?></h3>

					<?php if ( $document && ! $is_new ) : ?>
						<p>
							<strong><?php esc_html_e( 'Status:', 'ihumbak-invoices' ); ?></strong>
							<span class="ihumbak-status ihumbak-status-<?php echo esc_attr( $document->getStatus() ); ?>">
								<?php echo esc_html( $document->getStatusLabel() ); ?>
							</span>
						</p>
					<?php endif; ?>

					<?php if ( $can_edit ) : ?>
						<p>
							<button type="submit" name="save_action" value="draft" class="button button-large">
								<?php esc_html_e( 'Save as Draft', 'ihumbak-invoices' ); ?>
							</button>
						</p>
						<p>
							<button type="submit" name="save_action" value="issue" class="button button-primary button-large">
								<?php esc_html_e( 'Save and Issue', 'ihumbak-invoices' ); ?>
							</button>
						</p>
					<?php endif; ?>

					<?php include IHUMBAK_INVOICES_PATH . 'templates/admin/partials/revert-button.php'; ?>

					<?php if ( $document && ! $document->isDraft() ) : ?>
						<p>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=ihumbak-invoices&action=pdf&id=' . $document->getId() . '&nonce=' . wp_create_nonce( 'pdf_document_' . $document->getId() ) ) ); ?>"
							   class="button button-large" target="_blank">
								<?php esc_html_e( 'Download PDF', 'ihumbak-invoices' ); ?>
							</a>
						</p>
					<?php endif; ?>
				</div>

				<!-- Seller -->
				<?php include IHUMBAK_INVOICES_PATH . 'templates/admin/partials/seller-fields.php'; ?>

				<!-- Buyer -->
				<?php include IHUMBAK_INVOICES_PATH . 'templates/admin/partials/buyer-fields.php'; ?>
			</div>
		</div>
	</form>

	<?php if ( $pre_selected_invoice_id ) : ?>
	<script type="text/javascript">
		window.ihumbakPreSelectedInvoiceId = <?php echo (int) $pre_selected_invoice_id; ?>;
	</script>
	<?php endif; ?>
</div>
