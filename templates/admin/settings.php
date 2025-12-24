<?php
/**
 * Admin settings template.
 *
 * @package IHumbak\Invoices
 */

defined( 'ABSPATH' ) || exit;

$settings = get_option( 'ihumbak_invoices_settings', [] );
$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'seller';
?>

<div class="wrap ihumbak-invoices-settings-wrap">
    <h1><?php esc_html_e( 'Invoice Settings', 'ihumbak-invoices' ); ?></h1>

    <nav class="nav-tab-wrapper">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ihumbak-invoices-settings&tab=seller' ) ); ?>"
           class="nav-tab <?php echo 'seller' === $active_tab ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Seller Data', 'ihumbak-invoices' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ihumbak-invoices-settings&tab=numbering' ) ); ?>"
           class="nav-tab <?php echo 'numbering' === $active_tab ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Numbering', 'ihumbak-invoices' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ihumbak-invoices-settings&tab=pdf' ) ); ?>"
           class="nav-tab <?php echo 'pdf' === $active_tab ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'PDF', 'ihumbak-invoices' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ihumbak-invoices-settings&tab=automation' ) ); ?>"
           class="nav-tab <?php echo 'automation' === $active_tab ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Automation', 'ihumbak-invoices' ); ?>
        </a>
    </nav>

    <form method="post" action="options.php">
        <?php settings_fields( 'ihumbak_invoices_settings' ); ?>

        <?php if ( 'seller' === $active_tab ) : ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="seller_name"><?php esc_html_e( 'Company Name', 'ihumbak-invoices' ); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="seller_name"
                               name="ihumbak_invoices_settings[seller][name]"
                               value="<?php echo esc_attr( $settings['seller']['name'] ?? '' ); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="seller_nip"><?php esc_html_e( 'VAT (Tax ID)', 'ihumbak-invoices' ); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="seller_nip"
                               name="ihumbak_invoices_settings[seller][nip]"
                               value="<?php echo esc_attr( $settings['seller']['nip'] ?? '' ); ?>"
                               class="regular-text"
                               placeholder="1234567890">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="seller_address"><?php esc_html_e( 'Address', 'ihumbak-invoices' ); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="seller_address"
                               name="ihumbak_invoices_settings[seller][address]"
                               value="<?php echo esc_attr( $settings['seller']['address'] ?? '' ); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="seller_postcode"><?php esc_html_e( 'Postcode', 'ihumbak-invoices' ); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="seller_postcode"
                               name="ihumbak_invoices_settings[seller][postcode]"
                               value="<?php echo esc_attr( $settings['seller']['postcode'] ?? '' ); ?>"
                               class="regular-text"
                               placeholder="00-000">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="seller_city"><?php esc_html_e( 'City', 'ihumbak-invoices' ); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="seller_city"
                               name="ihumbak_invoices_settings[seller][city]"
                               value="<?php echo esc_attr( $settings['seller']['city'] ?? '' ); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="seller_country"><?php esc_html_e( 'Country', 'ihumbak-invoices' ); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="seller_country"
                               name="ihumbak_invoices_settings[seller][country]"
                               value="<?php echo esc_attr( $settings['seller']['country'] ?? 'Poland' ); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="seller_bank_name"><?php esc_html_e( 'Bank Name', 'ihumbak-invoices' ); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="seller_bank_name"
                               name="ihumbak_invoices_settings[seller][bank_name]"
                               value="<?php echo esc_attr( $settings['seller']['bank_name'] ?? '' ); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="seller_bank_account"><?php esc_html_e( 'Bank Account', 'ihumbak-invoices' ); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="seller_bank_account"
                               name="ihumbak_invoices_settings[seller][bank_account]"
                               value="<?php echo esc_attr( $settings['seller']['bank_account'] ?? '' ); ?>"
                               class="regular-text"
                               placeholder="PL00 0000 0000 0000 0000 0000 0000">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="seller_email"><?php esc_html_e( 'Email', 'ihumbak-invoices' ); ?></label>
                    </th>
                    <td>
                        <input type="email"
                               id="seller_email"
                               name="ihumbak_invoices_settings[seller][email]"
                               value="<?php echo esc_attr( $settings['seller']['email'] ?? '' ); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="seller_phone"><?php esc_html_e( 'Phone', 'ihumbak-invoices' ); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="seller_phone"
                               name="ihumbak_invoices_settings[seller][phone]"
                               value="<?php echo esc_attr( $settings['seller']['phone'] ?? '' ); ?>"
                               class="regular-text">
                    </td>
                </tr>
            </table>

        <?php elseif ( 'numbering' === $active_tab ) : ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="invoice_pattern"><?php esc_html_e( 'Invoice Pattern', 'ihumbak-invoices' ); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="invoice_pattern"
                               name="ihumbak_invoices_settings[numbering][invoice_pattern]"
                               value="<?php echo esc_attr( $settings['numbering']['invoice_pattern'] ?? 'FV/{YYYY}/{MM}/{NNNN}' ); ?>"
                               class="regular-text">
                        <p class="description">
                            <?php esc_html_e( 'Available placeholders: {YYYY}, {YY}, {MM}, {DD}, {NNNN}, {NNN}, {NN}', 'ihumbak-invoices' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="receipt_pattern"><?php esc_html_e( 'Receipt Pattern', 'ihumbak-invoices' ); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="receipt_pattern"
                               name="ihumbak_invoices_settings[numbering][receipt_pattern]"
                               value="<?php echo esc_attr( $settings['numbering']['receipt_pattern'] ?? 'PAR/{YYYY}/{MM}/{NNNN}' ); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="correction_pattern"><?php esc_html_e( 'Correction Pattern', 'ihumbak-invoices' ); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="correction_pattern"
                               name="ihumbak_invoices_settings[numbering][correction_pattern]"
                               value="<?php echo esc_attr( $settings['numbering']['correction_pattern'] ?? 'FK/{YYYY}/{MM}/{NNNN}' ); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Reset Monthly', 'ihumbak-invoices' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="ihumbak_invoices_settings[numbering][reset_monthly]"
                                   value="1"
                                   <?php checked( ! empty( $settings['numbering']['reset_monthly'] ) ); ?>>
                            <?php esc_html_e( 'Reset numbering at the beginning of each month', 'ihumbak-invoices' ); ?>
                        </label>
                    </td>
                </tr>
            </table>

        <?php elseif ( 'pdf' === $active_tab ) : ?>
            <?php
            $template_registry   = \IHumbak\Invoices\Core\Plugin::get_instance()->container()->get( 'pdf.template_registry' );
            $available_templates = $template_registry->getSelectOptions();
            $current_template    = $settings['pdf']['template'] ?? 'default';
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="pdf_template"><?php esc_html_e( 'PDF Template', 'ihumbak-invoices' ); ?></label>
                    </th>
                    <td>
                        <select id="pdf_template" name="ihumbak_invoices_settings[pdf][template]">
                            <?php foreach ( $available_templates as $template_key => $template_label ) : ?>
                                <option value="<?php echo esc_attr( $template_key ); ?>" <?php selected( $current_template, $template_key ); ?>>
                                    <?php echo esc_html( $template_label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php
                            printf(
                                /* translators: %s: theme directory path */
                                esc_html__( 'You can add custom templates in your theme: %s', 'ihumbak-invoices' ),
                                '<code>' . esc_html( get_stylesheet_directory() ) . '/ihumbak-invoices/{template-name}/</code>'
                            );
                            ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="pdf_logo"><?php esc_html_e( 'Logo', 'ihumbak-invoices' ); ?></label>
                    </th>
                    <td>
                        <input type="hidden"
                               id="pdf_logo_id"
                               name="ihumbak_invoices_settings[pdf][logo_id]"
                               value="<?php echo esc_attr( $settings['pdf']['logo_id'] ?? 0 ); ?>">
                        <button type="button" class="button" id="upload_logo_button">
                            <?php esc_html_e( 'Select Logo', 'ihumbak-invoices' ); ?>
                        </button>
                        <button type="button" class="button" id="remove_logo_button">
                            <?php esc_html_e( 'Remove Logo', 'ihumbak-invoices' ); ?>
                        </button>
                        <div id="logo_preview"></div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="pdf_footer"><?php esc_html_e( 'Footer Text', 'ihumbak-invoices' ); ?></label>
                    </th>
                    <td>
                        <textarea id="pdf_footer"
                                  name="ihumbak_invoices_settings[pdf][footer_text]"
                                  rows="3"
                                  class="large-text"><?php echo esc_textarea( $settings['pdf']['footer_text'] ?? '' ); ?></textarea>
                    </td>
                </tr>
            </table>

        <?php elseif ( 'automation' === $active_tab ) : ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Auto-generate Invoice', 'ihumbak-invoices' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="ihumbak_invoices_settings[automation][auto_generate_invoice]"
                                   value="1"
                                   <?php checked( ! empty( $settings['automation']['auto_generate_invoice'] ) ); ?>>
                            <?php esc_html_e( 'Automatically generate invoice when order status changes', 'ihumbak-invoices' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Auto-generate Receipt', 'ihumbak-invoices' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="ihumbak_invoices_settings[automation][auto_generate_receipt]"
                                   value="1"
                                   <?php checked( ! empty( $settings['automation']['auto_generate_receipt'] ) ); ?>>
                            <?php esc_html_e( 'Automatically generate receipt for orders without NIP', 'ihumbak-invoices' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="trigger_status"><?php esc_html_e( 'Trigger Status', 'ihumbak-invoices' ); ?></label>
                    </th>
                    <td>
                        <select id="trigger_status" name="ihumbak_invoices_settings[automation][trigger_status]">
                            <option value="processing" <?php selected( ( $settings['automation']['trigger_status'] ?? 'completed' ), 'processing' ); ?>>
                                <?php esc_html_e( 'Processing', 'ihumbak-invoices' ); ?>
                            </option>
                            <option value="completed" <?php selected( ( $settings['automation']['trigger_status'] ?? 'completed' ), 'completed' ); ?>>
                                <?php esc_html_e( 'Completed', 'ihumbak-invoices' ); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e( 'Order status that triggers automatic document generation', 'ihumbak-invoices' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="nip_meta_key"><?php esc_html_e( 'NIP Meta Key', 'ihumbak-invoices' ); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="nip_meta_key"
                               name="ihumbak_invoices_settings[automation][nip_meta_key]"
                               value="<?php echo esc_attr( $settings['automation']['nip_meta_key'] ?? '_billing_nip' ); ?>"
                               class="regular-text"
                               placeholder="_billing_nip">
                        <p class="description">
                            <?php esc_html_e( 'Order meta key where customer NIP/Tax ID is stored. Common values: _billing_nip, billing_nip, _vat_number', 'ihumbak-invoices' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
        <?php endif; ?>

        <?php submit_button(); ?>
    </form>
</div>
