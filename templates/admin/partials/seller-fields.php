<?php
/**
 * Seller fields partial.
 *
 * @package IHumbak\Invoices
 *
 * @var array<string, string> $seller   Seller data.
 * @var bool                  $can_edit Whether document can be edited.
 */

defined( 'ABSPATH' ) || exit;

$can_edit = $can_edit ?? true;
?>

<div class="ihumbak-card">
    <h3><?php esc_html_e( 'Seller', 'ihumbak-invoices' ); ?></h3>

    <div class="ihumbak-form-fields">
        <div class="ihumbak-form-field">
            <label for="seller_name"><?php esc_html_e( 'Company Name', 'ihumbak-invoices' ); ?> <span class="required">*</span></label>
            <input type="text" id="seller_name" name="seller[name]"
                   value="<?php echo esc_attr( $seller['name'] ?? '' ); ?>" required <?php wp_readonly( ! $can_edit ); ?>>
        </div>
        <div class="ihumbak-form-field">
            <label for="seller_details"><?php esc_html_e( 'Details', 'ihumbak-invoices' ); ?></label>
            <textarea id="seller_details" name="seller[details]" rows="6"
                      placeholder="<?php esc_attr_e( 'Address, VAT ID, bank, phone...', 'ihumbak-invoices' ); ?>" <?php wp_readonly( ! $can_edit ); ?>><?php echo esc_textarea( $seller['details'] ?? '' ); ?></textarea>
        </div>
    </div>
</div>
