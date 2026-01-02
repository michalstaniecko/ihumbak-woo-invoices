<?php
/**
 * Document items table partial - Two-row layout.
 *
 * @package IHumbak\Invoices
 *
 * @var array<int, array<string, mixed>> $items                   Document items.
 * @var bool                             $allow_negative_quantity Allow negative quantities (for credit notes).
 * @var bool                             $can_edit                Whether document can be edited.
 */

defined( 'ABSPATH' ) || exit;

$items                   = $items ?? array();
$allow_negative_quantity = $allow_negative_quantity ?? false;
$can_edit                = $can_edit ?? true;
$quantity_min_attr       = $allow_negative_quantity ? '' : ' min="0.001"';
$readonly_class          = $can_edit ? '' : ' ihumbak-readonly';
?>

<div class="ihumbak-card ihumbak-items-card<?php echo esc_attr( $readonly_class ); ?>">
	<h3><?php esc_html_e( 'Items', 'ihumbak-invoices' ); ?></h3>

	<?php if ( ! $can_edit ) : ?>
		<div class="ihumbak-readonly-notice">
			<span class="dashicons dashicons-lock"></span>
			<?php esc_html_e( 'This document has been issued. Item fields are read-only.', 'ihumbak-invoices' ); ?>
		</div>
	<?php endif; ?>

	<table class="widefat ihumbak-items-table ihumbak-items-table-tworow" id="ihumbak-items-table">
		<thead>
			<tr>
				<th class="column-sku"><?php esc_html_e( 'SKU', 'ihumbak-invoices' ); ?></th>
				<th class="column-quantity"><?php esc_html_e( 'Qty', 'ihumbak-invoices' ); ?></th>
				<th class="column-price-net"><?php esc_html_e( 'Price Net', 'ihumbak-invoices' ); ?></th>
				<th class="column-tax-rate"><?php esc_html_e( 'VAT %', 'ihumbak-invoices' ); ?></th>
				<th class="column-total-net"><?php esc_html_e( 'Total Net', 'ihumbak-invoices' ); ?></th>
				<th class="column-tax-amount"><?php esc_html_e( 'VAT', 'ihumbak-invoices' ); ?></th>
				<th class="column-total-gross"><?php esc_html_e( 'Total', 'ihumbak-invoices' ); ?></th>
				<th class="column-actions"></th>
			</tr>
		</thead>
		<tbody id="ihumbak-items-body">
			<?php if ( ! empty( $items ) ) : ?>
				<?php foreach ( $items as $index => $item ) : ?>
					<!-- Row 1: Name spanning all columns except actions -->
					<tr class="ihumbak-item-row ihumbak-item-row-name" data-index="<?php echo esc_attr( $index ); ?>">
						<td class="column-name" colspan="7">
							<input type="text" name="items[<?php echo esc_attr( $index ); ?>][name]"
									value="<?php echo esc_attr( $item['name'] ?? '' ); ?>"
									class="item-name" placeholder="<?php esc_attr_e( 'Product name', 'ihumbak-invoices' ); ?>" required <?php wp_readonly( ! $can_edit ); ?>>
							<!-- Hidden fields for data preservation -->
							<input type="hidden" name="items[<?php echo esc_attr( $index ); ?>][unit_price_gross]"
									value="<?php echo esc_attr( $item['unit_price_gross'] ?? '' ); ?>"
									class="item-price-gross">
							<input type="hidden" name="items[<?php echo esc_attr( $index ); ?>][unit]"
									value="<?php echo esc_attr( $item['unit'] ?? 'pcs' ); ?>"
									class="item-unit">
						</td>
						<td class="column-actions" rowspan="2">
							<?php if ( $can_edit ) : ?>
							<button type="button" class="button button-small ihumbak-remove-item" title="<?php esc_attr_e( 'Remove', 'ihumbak-invoices' ); ?>">
								<span class="dashicons dashicons-trash"></span>
							</button>
							<?php endif; ?>
						</td>
					</tr>
					<!-- Row 2: SKU + Numeric inputs -->
					<tr class="ihumbak-item-row ihumbak-item-row-values" data-index="<?php echo esc_attr( $index ); ?>">
						<td class="column-sku">
							<input type="text" name="items[<?php echo esc_attr( $index ); ?>][sku]"
									value="<?php echo esc_attr( $item['sku'] ?? '' ); ?>"
									class="item-sku" placeholder="<?php esc_attr_e( 'SKU', 'ihumbak-invoices' ); ?>" <?php wp_readonly( ! $can_edit ); ?>>
						</td>
						<td class="column-quantity">
							<input type="number" name="items[<?php echo esc_attr( $index ); ?>][quantity]"
									value="<?php echo esc_attr( $item['quantity'] ?? 1 ); ?>"
									class="item-quantity" step="0.001" placeholder="1"<?php echo esc_attr( $quantity_min_attr ); ?> required <?php wp_readonly( ! $can_edit ); ?>>
						</td>
						<td class="column-price-net">
							<input type="number" name="items[<?php echo esc_attr( $index ); ?>][unit_price_net]"
									value="<?php echo esc_attr( $item['unit_price_net'] ?? '' ); ?>"
									class="item-price-net" step="0.01" min="0" placeholder="0.00" <?php wp_readonly( ! $can_edit ); ?>>
						</td>
						<td class="column-tax-rate">
							<input type="number" name="items[<?php echo esc_attr( $index ); ?>][tax_rate]"
									value="<?php echo esc_attr( $item['tax_rate'] ?? 23 ); ?>"
									class="item-tax-rate" step="0.01" min="0" max="100" placeholder="23" <?php wp_readonly( ! $can_edit ); ?>>
						</td>
						<td class="column-total-net">
							<span class="item-total-net-display"><?php echo esc_html( number_format( (float) ( $item['line_total_net'] ?? 0 ), 2, ',', ' ' ) ); ?></span>
							<input type="hidden" name="items[<?php echo esc_attr( $index ); ?>][line_total_net]"
									value="<?php echo esc_attr( $item['line_total_net'] ?? 0 ); ?>"
									class="item-total-net">
						</td>
						<td class="column-tax-amount">
							<span class="item-tax-amount-display"><?php echo esc_html( number_format( (float) ( $item['tax_amount'] ?? 0 ), 2, ',', ' ' ) ); ?></span>
							<input type="hidden" name="items[<?php echo esc_attr( $index ); ?>][tax_amount]"
									value="<?php echo esc_attr( $item['tax_amount'] ?? 0 ); ?>"
									class="item-tax-amount">
						</td>
						<td class="column-total-gross">
							<span class="item-total-gross-display"><?php echo esc_html( number_format( (float) ( $item['line_total_gross'] ?? 0 ), 2, ',', ' ' ) ); ?></span>
							<input type="hidden" name="items[<?php echo esc_attr( $index ); ?>][line_total_gross]"
									value="<?php echo esc_attr( $item['line_total_gross'] ?? 0 ); ?>"
									class="item-total-gross">
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
		<tfoot>
			<tr class="ihumbak-totals-row">
				<td colspan="4" class="text-right"><strong><?php esc_html_e( 'Totals:', 'ihumbak-invoices' ); ?></strong></td>
				<td class="column-total-net">
					<strong id="document-subtotal-display">0,00</strong>
					<input type="hidden" name="subtotal" id="document-subtotal" value="0">
				</td>
				<td class="column-tax-amount">
					<strong id="document-tax-total-display">0,00</strong>
					<input type="hidden" name="tax_total" id="document-tax-total" value="0">
				</td>
				<td class="column-total-gross">
					<strong id="document-total-display">0,00</strong>
					<input type="hidden" name="total" id="document-total" value="0">
				</td>
				<td></td>
			</tr>
		</tfoot>
	</table>

	<?php if ( $can_edit ) : ?>
	<p>
		<button type="button" class="button" id="ihumbak-add-item">
			<span class="dashicons dashicons-plus-alt2"></span>
			<?php esc_html_e( 'Add Item', 'ihumbak-invoices' ); ?>
		</button>
	</p>
	<?php endif; ?>
</div>

<!-- Item row template for JS (two-row structure) -->
<script type="text/template" id="ihumbak-item-row-template">
	<!-- Row 1: Name spanning all columns except actions -->
	<tr class="ihumbak-item-row ihumbak-item-row-name" data-index="{{index}}">
		<td class="column-name" colspan="7">
			<input type="text" name="items[{{index}}][name]" value="" class="item-name" placeholder="<?php esc_attr_e( 'Product name', 'ihumbak-invoices' ); ?>" required>
			<input type="hidden" name="items[{{index}}][unit_price_gross]" value="" class="item-price-gross">
			<input type="hidden" name="items[{{index}}][unit]" value="pcs" class="item-unit">
		</td>
		<td class="column-actions" rowspan="2">
			<button type="button" class="button button-small ihumbak-remove-item" title="<?php esc_attr_e( 'Remove', 'ihumbak-invoices' ); ?>">
				<span class="dashicons dashicons-trash"></span>
			</button>
		</td>
	</tr>
	<!-- Row 2: SKU + Numeric inputs -->
	<tr class="ihumbak-item-row ihumbak-item-row-values" data-index="{{index}}">
		<td class="column-sku">
			<input type="text" name="items[{{index}}][sku]" value="" class="item-sku" placeholder="<?php esc_attr_e( 'SKU', 'ihumbak-invoices' ); ?>">
		</td>
		<td class="column-quantity">
			<input type="number" name="items[{{index}}][quantity]" value="1" class="item-quantity" step="0.001" placeholder="1"<?php echo esc_attr( $quantity_min_attr ); ?> required>
		</td>
		<td class="column-price-net">
			<input type="number" name="items[{{index}}][unit_price_net]" value="" class="item-price-net" step="0.01" min="0" placeholder="0.00">
		</td>
		<td class="column-tax-rate">
			<input type="number" name="items[{{index}}][tax_rate]" value="23" class="item-tax-rate" step="0.01" min="0" max="100" placeholder="23">
		</td>
		<td class="column-total-net">
			<span class="item-total-net-display">0,00</span>
			<input type="hidden" name="items[{{index}}][line_total_net]" value="0" class="item-total-net">
		</td>
		<td class="column-tax-amount">
			<span class="item-tax-amount-display">0,00</span>
			<input type="hidden" name="items[{{index}}][tax_amount]" value="0" class="item-tax-amount">
		</td>
		<td class="column-total-gross">
			<span class="item-total-gross-display">0,00</span>
			<input type="hidden" name="items[{{index}}][line_total_gross]" value="0" class="item-total-gross">
		</td>
	</tr>
</script>
