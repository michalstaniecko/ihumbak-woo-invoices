<?php
/**
 * Revert to Draft button partial.
 *
 * Displays the "Revert to Draft" button for super-admins on issued/sent/paid documents.
 *
 * @package IHumbak\Invoices
 *
 * @var \IHumbak\Invoices\Models\Document|null              $document            Document being edited.
 * @var \IHumbak\Invoices\Modules\Invoice\SuperAdminService $super_admin_service Super admin service.
 */

defined( 'ABSPATH' ) || exit;

// Only show for existing documents that are not draft and not cancelled.
if ( ! $document || $document->isDraft() || $document->isCancelled() ) {
	return;
}

// Only show for super-admins.
if ( ! $super_admin_service->isCurrentUserSuperAdmin() ) {
	return;
}
?>
<div class="ihumbak-revert-section">
	<p class="ihumbak-revert-header">
		<span class="dashicons dashicons-warning"></span>
		<?php esc_html_e( 'Super Admin Action', 'ihumbak-invoices' ); ?>
	</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
		  onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to revert this document to draft? This will allow editing of an issued document.', 'ihumbak-invoices' ) ); ?>');">
		<input type="hidden" name="action" value="ihumbak_revert_to_draft">
		<input type="hidden" name="document_id" value="<?php echo esc_attr( $document->getId() ); ?>">
		<?php wp_nonce_field( 'ihumbak_revert_to_draft', 'ihumbak_revert_nonce' ); ?>
		<button type="submit" class="button button-secondary">
			<span class="dashicons dashicons-undo"></span>
			<?php esc_html_e( 'Revert to Draft', 'ihumbak-invoices' ); ?>
		</button>
	</form>
	<p class="description">
		<?php esc_html_e( 'Warning: This will allow editing of an issued document.', 'ihumbak-invoices' ); ?>
	</p>
</div>
<?php
