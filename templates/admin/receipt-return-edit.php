<?php
/**
 * Receipt Return edit template.
 *
 * @package IHumbak\Invoices
 *
 * @var \IHumbak\Invoices\Models\ReceiptReturn|null             $document                   Document being edited (null for new).
 * @var \IHumbak\Invoices\Models\Document|null                  $original_document          Original receipt being returned.
 * @var array<string, string>                                   $seller                     Seller data.
 * @var array<string, string>                                   $buyer                      Buyer data.
 * @var array<int, array<string, mixed>>                        $items                      Document items.
 * @var array<int, \IHumbak\Invoices\Models\DocumentItem>       $original_items             Original receipt items.
 * @var \IHumbak\Invoices\Models\Document[]                     $available_receipts         Receipts available for returns.
 * @var array<int, \IHumbak\Invoices\Models\ReceiptReturn[]>    $existing_receipt_returns   Map of receipt ID to receipt returns.
 * @var array<int, array<string, mixed>>                        $available_refunds          WC refunds for the order.
 * @var string                                                  $next_number                Preview of next document number.
 * @var int|null                                                $pre_selected_receipt_id    Pre-selected receipt ID from URL.
 * @var \IHumbak\Invoices\Modules\Invoice\SuperAdminService     $super_admin_service        Super admin service.
 */

defined( 'ABSPATH' ) || exit;

use IHumbak\Invoices\Models\ReceiptReturn;

$is_new         = ! $document || ! $document->getId();
$is_draft       = ! $document || $document->isDraft();
$can_edit       = $is_new || $is_draft;
$is_manual_mode = $document && $document->isManualEntry();
$page_title = $is_new
	? __( 'New Receipt Return', 'ihumbak-invoices' )
	: sprintf(
		/* translators: %s: Document number */
		__( 'Edit Receipt Return: %s', 'ihumbak-invoices' ),
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
			<p><?php esc_html_e( 'Receipt Return saved successfully.', 'ihumbak-invoices' ); ?></p>
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
				<?php esc_html_e( 'Error saving Receipt Return.', 'ihumbak-invoices' ); ?>
				<?php if ( $error_message ) : ?>
					<br><small><?php echo esc_html( $error_message ); ?></small>
				<?php endif; ?>
			</p>
		</div>
		<?php
	elseif ( 'email_sent' === $message ) :
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Email sent successfully.', 'ihumbak-invoices' ); ?></p>
		</div>
		<?php
	elseif ( 'email_error' === $message ) :
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'Failed to send email. Please try again.', 'ihumbak-invoices' ); ?></p>
		</div>
		<?php
	endif;
	?>

	<?php if ( ! $can_edit ) : ?>
		<div class="notice notice-warning">
			<p><?php esc_html_e( 'This Receipt Return has been issued and cannot be edited.', 'ihumbak-invoices' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="notice notice-info">
		<p>
			<span class="dashicons dashicons-info" style="vertical-align: middle;"></span>
			<?php esc_html_e( 'Receipt Return is an informational document, not an official accounting document.', 'ihumbak-invoices' ); ?>
		</p>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="ihumbak-document-form">
		<input type="hidden" name="action" value="ihumbak_save_receipt_return">
		<input type="hidden" name="document_type" value="receipt_return">
		<?php wp_nonce_field( 'ihumbak_save_document', 'ihumbak_nonce' ); ?>

		<?php if ( $document && $document->getId() ) : ?>
			<input type="hidden" name="document_id" value="<?php echo esc_attr( $document->getId() ); ?>">
		<?php endif; ?>

		<div class="ihumbak-document-columns">
			<!-- Main column -->
			<div class="ihumbak-document-main">

				<!-- Source Receipt Selection -->
				<div class="ihumbak-card ihumbak-source-invoice-card">
					<h3><?php esc_html_e( 'Source Receipt', 'ihumbak-invoices' ); ?></h3>

					<input type="hidden" name="is_manual_entry" id="is_manual_entry" value="<?php echo $is_manual_mode ? '1' : '0'; ?>">

					<table class="form-table">
						<!-- Entry Mode Toggle -->
						<tr>
							<th><?php esc_html_e( 'Entry Mode', 'ihumbak-invoices' ); ?></th>
							<td>
								<label style="margin-right: 20px;">
									<input type="radio" name="entry_mode" value="system"
										   <?php checked( ! $is_manual_mode ); ?>
										   <?php disabled( ! $can_edit ); ?>>
									<?php esc_html_e( 'Select receipt from system', 'ihumbak-invoices' ); ?>
								</label>
								<label>
									<input type="radio" name="entry_mode" value="manual"
										   <?php checked( $is_manual_mode ); ?>
										   <?php disabled( ! $can_edit ); ?>>
									<?php esc_html_e( 'Enter original receipt data manually', 'ihumbak-invoices' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Use manual entry for receipts issued in a previous system.', 'ihumbak-invoices' ); ?>
								</p>
							</td>
						</tr>

						<!-- System Mode: Receipt Selection -->
						<tr id="system-mode-row" <?php echo $is_manual_mode ? 'style="display: none;"' : ''; ?>>
							<th>
								<label for="corrected_document_id">
									<?php esc_html_e( 'Select Receipt to Return', 'ihumbak-invoices' ); ?>
									<span class="required">*</span>
								</label>
							</th>
							<td>
								<select id="corrected_document_id" name="corrected_document_id"
										<?php disabled( ! $can_edit ); ?>>
									<option value=""><?php esc_html_e( '-- Select Receipt --', 'ihumbak-invoices' ); ?></option>
									<?php foreach ( $available_receipts as $rcpt ) : ?>
										<?php
										$selected = false;
										if ( $document && $document->getCorrectedDocumentId() === $rcpt->getId() ) {
											$selected = true;
										} elseif ( $pre_selected_receipt_id && $pre_selected_receipt_id === $rcpt->getId() ) {
											$selected = true;
										}
										$has_returns = isset( $existing_receipt_returns[ $rcpt->getId() ] );
										$label       = $rcpt->getDocumentNumber() . ' - ' .
											( $rcpt->getIssueDate() ? $rcpt->getIssueDate()->format( 'Y-m-d' ) : '' ) .
											' (' . number_format( $rcpt->getTotal(), 2 ) . ' ' . $rcpt->getCurrency() . ')';
										if ( $has_returns ) {
											$count  = count( $existing_receipt_returns[ $rcpt->getId() ] );
											$label .= ' ' . sprintf(
												/* translators: %d: Number of existing returns */
												_n( '[%d return]', '[%d returns]', $count, 'ihumbak-invoices' ),
												$count
											);
										}
										?>
										<option value="<?php echo esc_attr( $rcpt->getId() ); ?>"
												data-has-returns="<?php echo $has_returns ? '1' : '0'; ?>"
												<?php selected( $selected ); ?>>
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<?php if ( $can_edit ) : ?>
									<button type="button" id="ihumbak-load-receipt" class="button">
										<span class="dashicons dashicons-download" style="vertical-align: middle; margin-top: -2px;"></span>
										<?php esc_html_e( 'Load Receipt Data', 'ihumbak-invoices' ); ?>
									</button>
									<span id="ihumbak-load-status" class="spinner" style="float: none; margin: 4px 0 0 0;"></span>
								<?php endif; ?>
								<p id="return-warning" class="description" style="color: #d63638; display: none;">
									<span class="dashicons dashicons-warning" style="vertical-align: middle;"></span>
									<?php esc_html_e( 'This receipt already has existing returns. Creating another return may result in over-return.', 'ihumbak-invoices' ); ?>
								</p>
							</td>
						</tr>

						<!-- Manual Mode: Original Receipt Data -->
						<tr id="manual-mode-number-row" <?php echo ! $is_manual_mode ? 'style="display: none;"' : ''; ?>>
							<th>
								<label for="original_document_number">
									<?php esc_html_e( 'Original Receipt Number', 'ihumbak-invoices' ); ?>
									<span class="required">*</span>
								</label>
							</th>
							<td>
								<input type="text" id="original_document_number" name="original_document_number"
									   value="<?php echo esc_attr( $document ? $document->getOriginalDocumentNumber() : '' ); ?>"
									   class="regular-text" <?php disabled( ! $can_edit ); ?>>
								<p class="description">
									<?php esc_html_e( 'Enter the number of the original receipt from the previous system.', 'ihumbak-invoices' ); ?>
								</p>
							</td>
						</tr>
						<tr id="manual-mode-date-row" <?php echo ! $is_manual_mode ? 'style="display: none;"' : ''; ?>>
							<th>
								<label for="original_document_date">
									<?php esc_html_e( 'Original Receipt Date', 'ihumbak-invoices' ); ?>
								</label>
							</th>
							<td>
								<input type="date" id="original_document_date" name="original_document_date"
									   value="<?php echo esc_attr( $document && $document->getOriginalDocumentDate() ? $document->getOriginalDocumentDate()->format( 'Y-m-d' ) : '' ); ?>"
									   <?php disabled( ! $can_edit ); ?>>
								<p class="description">
									<?php esc_html_e( 'Optional: Enter the date of the original receipt.', 'ihumbak-invoices' ); ?>
								</p>
							</td>
						</tr>

						<!-- Original Receipt Info (shown when receipt is loaded) -->
						<?php if ( $original_document ) : ?>
						<tr id="original-receipt-info">
							<th><?php esc_html_e( 'Original Receipt', 'ihumbak-invoices' ); ?></th>
							<td>
								<div id="original-receipt-details">
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

				<!-- Return Details -->
				<div class="ihumbak-card">
					<h3><?php esc_html_e( 'Return Details', 'ihumbak-invoices' ); ?></h3>

					<table class="form-table">
						<tr>
							<th><label><?php esc_html_e( 'Return Type', 'ihumbak-invoices' ); ?></label></th>
							<td>
								<?php
								$current_type = $document ? $document->getCorrectionType() : ReceiptReturn::CORRECTION_TYPE_PARTIAL;
								?>
								<label style="margin-right: 20px;">
									<input type="radio" name="correction_type" value="partial"
										   <?php checked( $current_type, ReceiptReturn::CORRECTION_TYPE_PARTIAL ); ?>
										   <?php disabled( ! $can_edit ); ?>>
									<?php esc_html_e( 'Partial Return', 'ihumbak-invoices' ); ?>
								</label>
								<label>
									<input type="radio" name="correction_type" value="full"
										   <?php checked( $current_type, ReceiptReturn::CORRECTION_TYPE_FULL ); ?>
										   <?php disabled( ! $can_edit ); ?>>
									<?php esc_html_e( 'Full Return (Cancel Entire Receipt)', 'ihumbak-invoices' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th>
								<label for="correction_reason">
									<?php esc_html_e( 'Return Reason', 'ihumbak-invoices' ); ?>
								</label>
							</th>
							<td>
								<textarea id="correction_reason" name="correction_reason" rows="3"
										  class="large-text" <?php disabled( ! $can_edit ); ?>><?php
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

				<!-- Receipt Return Details -->
				<div class="ihumbak-card">
					<h3><?php esc_html_e( 'Receipt Return Details', 'ihumbak-invoices' ); ?></h3>

					<table class="form-table">
						<tr>
							<th><label for="document_number"><?php esc_html_e( 'Receipt Return Number', 'ihumbak-invoices' ); ?></label></th>
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
						</table>
				</div>

				<!-- Items -->
				<?php
				$require_nip             = false; // NIP is optional for receipt returns.
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
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=ihumbak-invoices&action=pdf&id=' . $document->getId() . '&force=1&nonce=' . wp_create_nonce( 'pdf_document_' . $document->getId() ) ) ); ?>"
							   class="button button-large" target="_blank">
								<?php esc_html_e( 'Regenerate PDF', 'ihumbak-invoices' ); ?>
							</a>
						</p>
						<?php
						// Show Send Email when document has linked order OR buyer email (for manual documents).
						$has_buyer_email = $document->getBuyer() && $document->getBuyer()->getEmail();
						if ( $document->getOrderId() || $has_buyer_email ) :
							?>
							<?php
							$email_url = add_query_arg(
								array(
									'page'      => 'ihumbak-invoices',
									'action'    => 'send_email',
									'id'        => $document->getId(),
									'return_to' => 'edit',
									'nonce'     => wp_create_nonce( 'send_email_' . $document->getId() ),
								),
								admin_url( 'admin.php' )
							);
							$was_sent = $document->wasSent();
							?>
							<p>
								<?php if ( $was_sent ) : ?>
									<a href="<?php echo esc_url( $email_url ); ?>"
									   class="button button-large ihumbak-resend-email"
									   data-confirm="<?php esc_attr_e( 'This document has already been sent. Do you want to send it again?', 'ihumbak-invoices' ); ?>">
										<?php esc_html_e( 'Resend Email', 'ihumbak-invoices' ); ?>
									</a>
								<?php else : ?>
									<a href="<?php echo esc_url( $email_url ); ?>" class="button button-large">
										<?php esc_html_e( 'Send Email', 'ihumbak-invoices' ); ?>
									</a>
								<?php endif; ?>
							</p>
							<?php if ( $was_sent ) : ?>
								<p class="description" style="margin-top: -10px;">
									<?php
									printf(
										/* translators: %s: Date and time when email was sent */
										esc_html__( 'Last sent: %s', 'ihumbak-invoices' ),
										esc_html( $document->getSentAt()->format( 'Y-m-d H:i' ) )
									);
									?>
								</p>
							<?php endif; ?>
						<?php endif; ?>
					<?php endif; ?>
				</div>

				<!-- Seller -->
				<?php include IHUMBAK_INVOICES_PATH . 'templates/admin/partials/seller-fields.php'; ?>

				<!-- Buyer -->
				<?php include IHUMBAK_INVOICES_PATH . 'templates/admin/partials/buyer-fields.php'; ?>
			</div>
		</div>
	</form>

	<?php if ( $pre_selected_receipt_id ) : ?>
	<script type="text/javascript">
		window.ihumbakPreSelectedReceiptId = <?php echo (int) $pre_selected_receipt_id; ?>;
	</script>
	<?php endif; ?>
</div>
