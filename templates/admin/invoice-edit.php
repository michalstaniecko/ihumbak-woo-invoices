<?php
/**
 * Invoice edit template.
 *
 * @package IHumbak\Invoices
 *
 * @var \IHumbak\Invoices\Models\Invoice|null $document            Document being edited (null for new).
 * @var array<string, string>                 $seller              Seller data.
 * @var array<string, string>                 $buyer               Buyer data.
 * @var array<int, array<string, mixed>>      $items               Document items.
 * @var string                                $next_number         Preview of next document number.
 * @var int|null                              $pre_filled_order_id Order ID to pre-fill (from WC order metabox).
 */

defined( 'ABSPATH' ) || exit;

$is_new     = ! $document || ! $document->getId();
$is_draft   = ! $document || $document->isDraft();
$can_edit   = $is_new || $is_draft;
$page_title = $is_new
    ? __( 'New Invoice', 'ihumbak-invoices' )
    : sprintf(
        /* translators: %s: Document number */
        __( 'Edit Invoice: %s', 'ihumbak-invoices' ),
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
            <p><?php esc_html_e( 'Document saved successfully.', 'ihumbak-invoices' ); ?></p>
        </div>
        <?php
    elseif ( 'error' === $message ) :
        $error_message = get_transient( 'ihumbak_save_error_' . get_current_user_id() );
        delete_transient( 'ihumbak_save_error_' . get_current_user_id() );
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php esc_html_e( 'Error saving document.', 'ihumbak-invoices' ); ?>
                <?php if ( $error_message ) : ?>
                    <br><small><?php echo esc_html( $error_message ); ?></small>
                <?php endif; ?>
            </p>
        </div>
        <?php
    endif;
    ?>

    <?php if ( ! $can_edit ) : ?>
        <div class="notice notice-warning">
            <p><?php esc_html_e( 'This document has been issued and cannot be edited.', 'ihumbak-invoices' ); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="ihumbak-document-form">
        <input type="hidden" name="action" value="ihumbak_save_invoice">
        <input type="hidden" name="document_type" value="invoice">
        <?php wp_nonce_field( 'ihumbak_save_document', 'ihumbak_nonce' ); ?>

        <?php if ( $document && $document->getId() ) : ?>
            <input type="hidden" name="document_id" value="<?php echo esc_attr( $document->getId() ); ?>">
        <?php endif; ?>

        <div class="ihumbak-document-columns">
            <!-- Main column -->
            <div class="ihumbak-document-main">

                <!-- Document details -->
                <div class="ihumbak-card">
                    <h3><?php esc_html_e( 'Invoice Details', 'ihumbak-invoices' ); ?></h3>

                    <table class="form-table">
                        <tr>
                            <th><label for="document_number"><?php esc_html_e( 'Invoice Number', 'ihumbak-invoices' ); ?></label></th>
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
                        <tr>
                            <th><label for="sale_date"><?php esc_html_e( 'Sale Date', 'ihumbak-invoices' ); ?> <span class="required">*</span></label></th>
                            <td>
                                <input type="date" id="sale_date" name="sale_date"
                                       value="<?php echo esc_attr( $document ? $document->getSaleDate()?->format( 'Y-m-d' ) : gmdate( 'Y-m-d' ) ); ?>"
                                       required <?php disabled( ! $can_edit ); ?>>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="due_date"><?php esc_html_e( 'Due Date', 'ihumbak-invoices' ); ?></label></th>
                            <td>
                                <?php
                                $default_due_date = gmdate( 'Y-m-d', strtotime( '+14 days' ) );
                                $due_date         = $document ? $document->getDueDate()?->format( 'Y-m-d' ) : $default_due_date;
                                ?>
                                <input type="date" id="due_date" name="due_date"
                                       value="<?php echo esc_attr( $due_date ); ?>"
                                       <?php disabled( ! $can_edit ); ?>>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="payment_method"><?php esc_html_e( 'Payment Method', 'ihumbak-invoices' ); ?></label></th>
                            <td>
                                <select id="payment_method" name="payment_method" <?php disabled( ! $can_edit ); ?>>
                                    <option value=""><?php esc_html_e( '— Select —', 'ihumbak-invoices' ); ?></option>
                                    <?php foreach ( \IHumbak\Invoices\Models\Invoice::getPaymentMethods() as $method => $label ) : ?>
                                        <option value="<?php echo esc_attr( $method ); ?>"
                                                <?php selected( $document ? $document->getPaymentMethod() : '', $method ); ?>>
                                            <?php echo esc_html( $label ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="order_id"><?php esc_html_e( 'WooCommerce Order', 'ihumbak-invoices' ); ?></label></th>
                            <td>
                                <div class="ihumbak-order-field-wrapper">
                                    <?php
                                    $order_id_value = '';
                                    if ( $document && $document->getOrderId() ) {
                                        $order_id_value = $document->getOrderId();
                                    } elseif ( ! empty( $pre_filled_order_id ) ) {
                                        $order_id_value = $pre_filled_order_id;
                                    }
                                    ?>
                                    <input type="number" id="order_id" name="order_id"
                                           value="<?php echo esc_attr( $order_id_value ); ?>"
                                           class="small-text" min="1" <?php disabled( ! $can_edit ); ?>>
                                    <?php if ( $can_edit ) : ?>
                                    <button type="button" id="ihumbak-fetch-order" class="button" disabled>
                                        <span class="dashicons dashicons-download" style="vertical-align: middle; margin-top: -2px;"></span>
                                        <?php esc_html_e( 'Fetch Order Data', 'ihumbak-invoices' ); ?>
                                    </button>
                                    <span id="ihumbak-fetch-status" class="spinner" style="float: none; margin: 4px 0 0 0;"></span>
                                    <?php endif; ?>
                                </div>
                                <p class="description"><?php esc_html_e( 'Enter order number and click "Fetch Order Data" to import items and buyer info.', 'ihumbak-invoices' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Items -->
                <?php
                $require_nip = true;
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
                    <?php endif; ?>
                </div>

                <!-- Seller -->
                <?php include IHUMBAK_INVOICES_PATH . 'templates/admin/partials/seller-fields.php'; ?>

                <!-- Buyer -->
                <?php include IHUMBAK_INVOICES_PATH . 'templates/admin/partials/buyer-fields.php'; ?>
            </div>
        </div>
    </form>

    <?php if ( ! empty( $pre_filled_order_id ) ) : ?>
    <script type="text/javascript">
        window.ihumbakPreFilledOrderId = <?php echo (int) $pre_filled_order_id; ?>;
    </script>
    <?php endif; ?>
</div>
