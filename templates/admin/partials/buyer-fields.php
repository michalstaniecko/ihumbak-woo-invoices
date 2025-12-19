<?php
/**
 * Buyer fields partial.
 *
 * @package IHumbak\Invoices
 *
 * @var array<string, string> $buyer       Buyer data.
 * @var bool                  $require_nip Whether NIP is required (true for invoices).
 */

defined( 'ABSPATH' ) || exit;

$require_nip = $require_nip ?? true;
?>

<div class="ihumbak-card">
    <h3><?php esc_html_e( 'Buyer', 'ihumbak-invoices' ); ?></h3>

    <table class="form-table">
        <tr>
            <th><label for="buyer_name"><?php esc_html_e( 'Name / Company', 'ihumbak-invoices' ); ?> <span class="required">*</span></label></th>
            <td>
                <input type="text" id="buyer_name" name="buyer[name]"
                       value="<?php echo esc_attr( $buyer['name'] ?? '' ); ?>"
                       class="regular-text" required>
            </td>
        </tr>
        <?php if ( $require_nip ) : ?>
        <tr>
            <th><label for="buyer_nip"><?php esc_html_e( 'NIP', 'ihumbak-invoices' ); ?> <span class="required">*</span></label></th>
            <td>
                <input type="text" id="buyer_nip" name="buyer[nip]"
                       value="<?php echo esc_attr( $buyer['nip'] ?? '' ); ?>"
                       class="regular-text" required>
            </td>
        </tr>
        <?php else : ?>
        <tr>
            <th><label for="buyer_nip"><?php esc_html_e( 'NIP', 'ihumbak-invoices' ); ?></label></th>
            <td>
                <input type="text" id="buyer_nip" name="buyer[nip]"
                       value="<?php echo esc_attr( $buyer['nip'] ?? '' ); ?>"
                       class="regular-text">
                <p class="description"><?php esc_html_e( 'Optional for receipts', 'ihumbak-invoices' ); ?></p>
            </td>
        </tr>
        <?php endif; ?>
        <tr>
            <th><label for="buyer_address"><?php esc_html_e( 'Address', 'ihumbak-invoices' ); ?></label></th>
            <td>
                <input type="text" id="buyer_address" name="buyer[address]"
                       value="<?php echo esc_attr( $buyer['address'] ?? '' ); ?>"
                       class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="buyer_postcode"><?php esc_html_e( 'Postcode', 'ihumbak-invoices' ); ?></label></th>
            <td>
                <input type="text" id="buyer_postcode" name="buyer[postcode]"
                       value="<?php echo esc_attr( $buyer['postcode'] ?? '' ); ?>"
                       class="small-text">
            </td>
        </tr>
        <tr>
            <th><label for="buyer_city"><?php esc_html_e( 'City', 'ihumbak-invoices' ); ?></label></th>
            <td>
                <input type="text" id="buyer_city" name="buyer[city]"
                       value="<?php echo esc_attr( $buyer['city'] ?? '' ); ?>"
                       class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="buyer_country"><?php esc_html_e( 'Country', 'ihumbak-invoices' ); ?></label></th>
            <td>
                <input type="text" id="buyer_country" name="buyer[country]"
                       value="<?php echo esc_attr( $buyer['country'] ?? 'PL' ); ?>"
                       class="small-text">
            </td>
        </tr>
        <tr>
            <th><label for="buyer_email"><?php esc_html_e( 'Email', 'ihumbak-invoices' ); ?></label></th>
            <td>
                <input type="email" id="buyer_email" name="buyer[email]"
                       value="<?php echo esc_attr( $buyer['email'] ?? '' ); ?>"
                       class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="buyer_phone"><?php esc_html_e( 'Phone', 'ihumbak-invoices' ); ?></label></th>
            <td>
                <input type="text" id="buyer_phone" name="buyer[phone]"
                       value="<?php echo esc_attr( $buyer['phone'] ?? '' ); ?>"
                       class="regular-text">
            </td>
        </tr>
    </table>
</div>
