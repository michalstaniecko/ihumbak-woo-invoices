<?php
/**
 * Order Status Change checkbox partial.
 *
 * Displays checkbox for changing order status when issuing a document.
 *
 * @package IHumbak\Invoices
 *
 * @var bool     $can_edit       Whether the document can be edited.
 * @var int|null $order_id_value Current order ID value (from document or pre-filled).
 */

use IHumbak\Invoices\Modules\Invoice\OrderStatusService;

defined( 'ABSPATH' ) || exit;

// Create service instance.
$order_status_service = new OrderStatusService();

// Only render if feature is enabled.
if ( ! $order_status_service->isEnabled() ) {
	return;
}

// Only render if document can be edited.
if ( empty( $can_edit ) ) {
	return;
}

$target_status_label = $order_status_service->getTargetStatusLabel();
$has_order_id        = ! empty( $order_id_value );
$default_checked     = $order_status_service->getCheckboxDefault( $order_id_value );

// Hidden by default if no order ID - will be shown via JS when order is selected.
$wrapper_style = $has_order_id ? '' : 'display: none;';
?>
<div id="ihumbak-order-status-change-wrapper" class="ihumbak-order-status-change" style="<?php echo esc_attr( $wrapper_style ); ?>">
	<hr style="margin: 15px 0;">
	<p>
		<label>
			<input type="checkbox"
				   id="change_order_status"
				   name="change_order_status"
				   value="1"
				   <?php checked( $default_checked ); ?>>
			<?php
			printf(
				/* translators: %s: target order status */
				esc_html__( 'Change order status to "%s" when issuing', 'ihumbak-invoices' ),
				esc_html( $target_status_label )
			);
			?>
		</label>
	</p>
	<p class="description">
		<?php esc_html_e( 'The order status will be changed automatically when you issue this document.', 'ihumbak-invoices' ); ?>
	</p>
</div>
<?php
