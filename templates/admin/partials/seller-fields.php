<?php
/**
 * Seller fields partial.
 *
 * @package IHumbak\Invoices
 *
 * @var array<string, string> $seller Seller data.
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="ihumbak-card">
    <h3><?php esc_html_e( 'Seller', 'ihumbak-invoices' ); ?></h3>

    <div class="ihumbak-form-fields">
        <div class="ihumbak-form-field">
            <label for="seller_name"><?php esc_html_e( 'Company Name', 'ihumbak-invoices' ); ?> <span class="required">*</span></label>
            <input type="text" id="seller_name" name="seller[name]"
                   value="<?php echo esc_attr( $seller['name'] ?? '' ); ?>" required>
        </div>
        <div class="ihumbak-form-field">
            <label for="seller_nip"><?php esc_html_e( 'NIP', 'ihumbak-invoices' ); ?> <span class="required">*</span></label>
            <input type="text" id="seller_nip" name="seller[nip]"
                   value="<?php echo esc_attr( $seller['nip'] ?? '' ); ?>" required>
        </div>
        <div class="ihumbak-form-field">
            <label for="seller_address"><?php esc_html_e( 'Address', 'ihumbak-invoices' ); ?></label>
            <textarea id="seller_address" name="seller[address]" rows="2"><?php echo esc_textarea( $seller['address'] ?? '' ); ?></textarea>
        </div>
        <div class="ihumbak-form-field">
            <label for="seller_postcode"><?php esc_html_e( 'Postcode', 'ihumbak-invoices' ); ?></label>
            <input type="text" id="seller_postcode" name="seller[postcode]"
                   value="<?php echo esc_attr( $seller['postcode'] ?? '' ); ?>">
        </div>
        <div class="ihumbak-form-field">
            <label for="seller_city"><?php esc_html_e( 'City', 'ihumbak-invoices' ); ?></label>
            <input type="text" id="seller_city" name="seller[city]"
                   value="<?php echo esc_attr( $seller['city'] ?? '' ); ?>">
        </div>
        <div class="ihumbak-form-field">
            <label for="seller_country"><?php esc_html_e( 'Country', 'ihumbak-invoices' ); ?></label>
            <input type="text" id="seller_country" name="seller[country]"
                   value="<?php echo esc_attr( $seller['country'] ?? 'PL' ); ?>">
        </div>
        <div class="ihumbak-form-field">
            <label for="seller_bank_name"><?php esc_html_e( 'Bank Name', 'ihumbak-invoices' ); ?></label>
            <input type="text" id="seller_bank_name" name="seller[bank_name]"
                   value="<?php echo esc_attr( $seller['bank_name'] ?? '' ); ?>">
        </div>
        <div class="ihumbak-form-field">
            <label for="seller_bank_account"><?php esc_html_e( 'Bank Account', 'ihumbak-invoices' ); ?></label>
            <input type="text" id="seller_bank_account" name="seller[bank_account]"
                   value="<?php echo esc_attr( $seller['bank_account'] ?? '' ); ?>">
        </div>
    </div>
</div>
