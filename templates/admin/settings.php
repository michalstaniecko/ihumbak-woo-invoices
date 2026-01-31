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
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ihumbak-invoices-settings&tab=display' ) ); ?>"
           class="nav-tab <?php echo 'display' === $active_tab ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Display', 'ihumbak-invoices' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ihumbak-invoices-settings&tab=permissions' ) ); ?>"
           class="nav-tab <?php echo 'permissions' === $active_tab ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Permissions', 'ihumbak-invoices' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ihumbak-invoices-settings&tab=email' ) ); ?>"
           class="nav-tab <?php echo 'email' === $active_tab ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Email', 'ihumbak-invoices' ); ?>
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
                        <label for="seller_details"><?php esc_html_e( 'Company Details', 'ihumbak-invoices' ); ?></label>
                    </th>
                    <td>
                        <textarea id="seller_details"
                                  name="ihumbak_invoices_settings[seller][details]"
                                  rows="8"
                                  class="large-text"
                                  placeholder="<?php esc_attr_e( 'Address, VAT ID, bank account, phone, email...', 'ihumbak-invoices' ); ?>"><?php echo esc_textarea( $settings['seller']['details'] ?? '' ); ?></textarea>
                        <p class="description">
                            <?php esc_html_e( 'Enter all company details including address, VAT ID (NIP), bank account, phone and email. Each line will be displayed separately in documents.', 'ihumbak-invoices' ); ?>
                        </p>
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
                    <th scope="row">
                        <label for="receipt_return_pattern"><?php esc_html_e( 'Receipt Return Pattern', 'ihumbak-invoices' ); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="receipt_return_pattern"
                               name="ihumbak_invoices_settings[numbering][receipt_return_pattern]"
                               value="<?php echo esc_attr( $settings['numbering']['receipt_return_pattern'] ?? 'RR/{YYYY}/{MM}/{NNNN}' ); ?>"
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

            <?php
            $super_admin_service = new \IHumbak\Invoices\Modules\Invoice\SuperAdminService();
            if ( $super_admin_service->isCurrentUserSuperAdmin() ) :
            ?>
            <h2><?php esc_html_e( 'Counter Adjustment', 'ihumbak-invoices' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Adjust the next document number. Use when documents were deleted after being reverted to draft.', 'ihumbak-invoices' ); ?>
            </p>

            <div class="notice notice-warning inline" style="margin: 10px 0;">
                <p>
                    <strong><?php esc_html_e( 'Warning:', 'ihumbak-invoices' ); ?></strong>
                    <?php esc_html_e( 'Changing these values may cause numbering gaps or duplicates. Use with caution.', 'ihumbak-invoices' ); ?>
                </p>
            </div>

            <table class="form-table" id="ihumbak-numbering-adjustment">
                <tbody>
                    <!-- Populated via JavaScript -->
                </tbody>
            </table>
            <p id="ihumbak-numbering-loading"><?php esc_html_e( 'Loading...', 'ihumbak-invoices' ); ?></p>
            <?php endif; ?>

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

        <?php elseif ( 'display' === $active_tab ) : ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Orders List Column', 'ihumbak-invoices' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="ihumbak_invoices_settings[display][show_order_column]"
                                   value="1"
                                   <?php checked( ! empty( $settings['display']['show_order_column'] ) ); ?>>
                            <?php esc_html_e( 'Show documents column in WooCommerce orders list', 'ihumbak-invoices' ); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e( 'Displays invoice, receipt and credit note numbers directly in the orders list.', 'ihumbak-invoices' ); ?>
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
                               name="ihumbak_invoices_settings[display][nip_meta_key]"
                               value="<?php echo esc_attr( $settings['display']['nip_meta_key'] ?? '_billing_nip' ); ?>"
                               class="regular-text"
                               placeholder="_billing_nip">
                        <p class="description">
                            <?php esc_html_e( 'Order meta key where customer NIP/Tax ID is stored. Common values: _billing_nip, billing_nip, _vat_number', 'ihumbak-invoices' ); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Order Status Change', 'ihumbak-invoices' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Configure automatic order status change when manually issuing invoices or receipts.', 'ihumbak-invoices' ); ?>
            </p>

            <?php
            $order_status_service = new \IHumbak\Invoices\Modules\Invoice\OrderStatusService();
            $order_statuses       = $order_status_service->getOrderStatuses();
            $current_status       = $settings['display']['order_status']['target'] ?? 'completed';
            $is_enabled           = ! empty( $settings['display']['order_status']['enabled'] );
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Enable Status Change', 'ihumbak-invoices' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   id="order_status_enabled"
                                   name="ihumbak_invoices_settings[display][order_status][enabled]"
                                   value="1"
                                   <?php checked( $is_enabled ); ?>>
                            <?php esc_html_e( 'Enable automatic order status change when generating documents', 'ihumbak-invoices' ); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e( 'When enabled, a checkbox will appear on invoice/receipt edit pages allowing you to change the order status when issuing a document.', 'ihumbak-invoices' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="order_status_target"><?php esc_html_e( 'Target Status', 'ihumbak-invoices' ); ?></label>
                    </th>
                    <td>
                        <select id="order_status_target" name="ihumbak_invoices_settings[display][order_status][target]">
                            <?php foreach ( $order_statuses as $status_slug => $status_label ) : ?>
                                <option value="<?php echo esc_attr( $status_slug ); ?>" <?php selected( $current_status, $status_slug ); ?>>
                                    <?php echo esc_html( $status_label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e( 'Select the order status to set when issuing a document.', 'ihumbak-invoices' ); ?>
                        </p>
                    </td>
                </tr>
            </table>

        <?php elseif ( 'permissions' === $active_tab ) : ?>
            <?php $current_role = $settings['permissions']['minimum_role'] ?? $permission_default_role; ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="minimum_role"><?php esc_html_e( 'Minimum Role for Documents', 'ihumbak-invoices' ); ?></label>
                    </th>
                    <td>
                        <select id="minimum_role" name="ihumbak_invoices_settings[permissions][minimum_role]">
                            <?php foreach ( $permission_available_roles as $role => $label ) : ?>
                                <option value="<?php echo esc_attr( $role ); ?>" <?php selected( $current_role, $role ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e( 'Select the minimum role required to create, edit, and manage invoices, receipts, and credit notes.', 'ihumbak-invoices' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <div class="notice notice-info inline" style="margin: 20px 0;">
                <p>
                    <strong><?php esc_html_e( 'Note:', 'ihumbak-invoices' ); ?></strong>
                    <?php esc_html_e( 'Plugin settings (this page) are always restricted to Administrators only.', 'ihumbak-invoices' ); ?>
                </p>
            </div>

        <?php elseif ( 'email' === $active_tab ) : ?>
            <h2><?php esc_html_e( 'Auto-Send Settings', 'ihumbak-invoices' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Configure automatic email sending when documents are issued. Emails include the document PDF as an attachment.', 'ihumbak-invoices' ); ?>
            </p>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Auto-Send Invoice', 'ihumbak-invoices' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="ihumbak_invoices_settings[email][auto_send_invoice]"
                                   value="1"
                                   <?php checked( ! empty( $settings['email']['auto_send_invoice'] ) ); ?>>
                            <?php esc_html_e( 'Automatically send email when an invoice is issued', 'ihumbak-invoices' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Auto-Send Receipt', 'ihumbak-invoices' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="ihumbak_invoices_settings[email][auto_send_receipt]"
                                   value="1"
                                   <?php checked( ! empty( $settings['email']['auto_send_receipt'] ) ); ?>>
                            <?php esc_html_e( 'Automatically send email when a receipt is issued', 'ihumbak-invoices' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Auto-Send Credit Note', 'ihumbak-invoices' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="ihumbak_invoices_settings[email][auto_send_credit_note]"
                                   value="1"
                                   <?php checked( ! empty( $settings['email']['auto_send_credit_note'] ) ); ?>>
                            <?php esc_html_e( 'Automatically send email when a credit note is issued', 'ihumbak-invoices' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Auto-Send Receipt Return', 'ihumbak-invoices' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="ihumbak_invoices_settings[email][auto_send_receipt_return]"
                                   value="1"
                                   <?php checked( ! empty( $settings['email']['auto_send_receipt_return'] ) ); ?>>
                            <?php esc_html_e( 'Automatically send email when a receipt return is issued', 'ihumbak-invoices' ); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <div class="notice notice-info inline" style="margin: 20px 0;">
                <p>
                    <strong><?php esc_html_e( 'Note:', 'ihumbak-invoices' ); ?></strong>
                    <?php esc_html_e( 'Emails are sent to the billing email address from the linked WooCommerce order. Documents without a linked order cannot be sent automatically.', 'ihumbak-invoices' ); ?>
                </p>
            </div>

            <h2><?php esc_html_e( 'Email Templates', 'ihumbak-invoices' ); ?></h2>
            <p>
                <?php
                printf(
                    /* translators: %s: Link to WooCommerce email settings */
                    esc_html__( 'Email templates can be customized in %s. Look for "Invoice", "Receipt", and "Credit Note" emails.', 'ihumbak-invoices' ),
                    '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=email' ) ) . '">' . esc_html__( 'WooCommerce > Settings > Emails', 'ihumbak-invoices' ) . '</a>'
                );
                ?>
            </p>
        <?php endif; ?>

        <?php submit_button(); ?>
    </form>
</div>
