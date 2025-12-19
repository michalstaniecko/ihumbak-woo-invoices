<?php
/**
 * Admin invoices list template.
 *
 * @package IHumbak\Invoices
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap ihumbak-invoices-wrap">
    <h1 class="wp-heading-inline">
        <?php esc_html_e( 'Invoices', 'ihumbak-invoices' ); ?>
    </h1>

    <hr class="wp-header-end">

    <div class="ihumbak-invoices-content">
        <div class="notice notice-info">
            <p>
                <?php esc_html_e( 'Invoice management coming soon. Configure your settings first.', 'ihumbak-invoices' ); ?>
            </p>
        </div>

        <p>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ihumbak-invoices-settings' ) ); ?>" class="button button-primary">
                <?php esc_html_e( 'Go to Settings', 'ihumbak-invoices' ); ?>
            </a>
        </p>
    </div>
</div>
