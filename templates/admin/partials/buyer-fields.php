<?php
/**
 * Buyer fields partial.
 *
 * @package IHumbak\Invoices
 *
 * @var array<string, string> $buyer       Buyer data.
 * @var bool                  $require_nip Whether NIP is required (true for invoices).
 * @var bool                  $can_edit    Whether document can be edited.
 */

defined( 'ABSPATH' ) || exit;

$require_nip   = $require_nip ?? true;
$can_edit      = $can_edit ?? true;
$readonly_attr = $can_edit ? '' : ' readonly';
?>

<div class="ihumbak-card">
    <h3><?php esc_html_e( 'Buyer', 'ihumbak-invoices' ); ?></h3>

    <div class="ihumbak-form-fields">
        <div class="ihumbak-form-field">
            <label for="buyer_name"><?php esc_html_e( 'Name / Company', 'ihumbak-invoices' ); ?> <span class="required">*</span></label>
            <input type="text" id="buyer_name" name="buyer[name]"
                   value="<?php echo esc_attr( $buyer['name'] ?? '' ); ?>" required<?php echo esc_attr( $readonly_attr ); ?>>
        </div>
        <?php if ( $require_nip ) : ?>
        <div class="ihumbak-form-field">
            <label for="buyer_nip"><?php esc_html_e( 'NIP', 'ihumbak-invoices' ); ?> <span class="required">*</span></label>
            <input type="text" id="buyer_nip" name="buyer[nip]"
                   value="<?php echo esc_attr( $buyer['nip'] ?? '' ); ?>" required<?php echo esc_attr( $readonly_attr ); ?>>
        </div>
        <?php else : ?>
        <div class="ihumbak-form-field">
            <label for="buyer_nip"><?php esc_html_e( 'NIP', 'ihumbak-invoices' ); ?></label>
            <input type="text" id="buyer_nip" name="buyer[nip]"
                   value="<?php echo esc_attr( $buyer['nip'] ?? '' ); ?>"<?php echo esc_attr( $readonly_attr ); ?>>
            <p class="description"><?php esc_html_e( 'Optional for receipts', 'ihumbak-invoices' ); ?></p>
        </div>
        <?php endif; ?>
        <div class="ihumbak-form-field">
            <label for="buyer_address"><?php esc_html_e( 'Address', 'ihumbak-invoices' ); ?></label>
            <textarea id="buyer_address" name="buyer[address]" rows="2"<?php echo esc_attr( $readonly_attr ); ?>><?php echo esc_textarea( $buyer['address'] ?? '' ); ?></textarea>
        </div>
        <div class="ihumbak-form-field">
            <label for="buyer_postcode"><?php esc_html_e( 'Postcode', 'ihumbak-invoices' ); ?></label>
            <input type="text" id="buyer_postcode" name="buyer[postcode]"
                   value="<?php echo esc_attr( $buyer['postcode'] ?? '' ); ?>"<?php echo esc_attr( $readonly_attr ); ?>>
        </div>
        <div class="ihumbak-form-field">
            <label for="buyer_city"><?php esc_html_e( 'City', 'ihumbak-invoices' ); ?></label>
            <input type="text" id="buyer_city" name="buyer[city]"
                   value="<?php echo esc_attr( $buyer['city'] ?? '' ); ?>"<?php echo esc_attr( $readonly_attr ); ?>>
        </div>
        <div class="ihumbak-form-field">
            <label for="buyer_country"><?php esc_html_e( 'Country', 'ihumbak-invoices' ); ?></label>
            <input type="text" id="buyer_country" name="buyer[country]"
                   value="<?php echo esc_attr( $buyer['country'] ?? 'PL' ); ?>"<?php echo esc_attr( $readonly_attr ); ?>>
        </div>
        <div class="ihumbak-form-field">
            <label for="buyer_email"><?php esc_html_e( 'Email', 'ihumbak-invoices' ); ?></label>
            <input type="email" id="buyer_email" name="buyer[email]"
                   value="<?php echo esc_attr( $buyer['email'] ?? '' ); ?>"<?php echo esc_attr( $readonly_attr ); ?>>
        </div>
        <div class="ihumbak-form-field">
            <label for="buyer_phone"><?php esc_html_e( 'Phone', 'ihumbak-invoices' ); ?></label>
            <input type="text" id="buyer_phone" name="buyer[phone]"
                   value="<?php echo esc_attr( $buyer['phone'] ?? '' ); ?>"<?php echo esc_attr( $readonly_attr ); ?>>
        </div>
    </div>
</div>
