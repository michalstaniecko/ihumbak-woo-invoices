<?php
/**
 * Admin documents list template.
 *
 * @package IHumbak\Invoices
 *
 * @var \IHumbak\Invoices\Modules\Admin\DocumentListTable $list_table
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap ihumbak-invoices-wrap">
    <h1 class="wp-heading-inline">
        <?php esc_html_e( 'Documents', 'ihumbak-invoices' ); ?>
    </h1>

    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ihumbak-invoices&action=new&type=invoice' ) ); ?>" class="page-title-action">
        <?php esc_html_e( 'Add Invoice', 'ihumbak-invoices' ); ?>
    </a>

    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ihumbak-invoices&action=new&type=receipt' ) ); ?>" class="page-title-action">
        <?php esc_html_e( 'Add Receipt', 'ihumbak-invoices' ); ?>
    </a>

    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ihumbak-invoices&action=new&type=credit_note' ) ); ?>" class="page-title-action">
        <?php esc_html_e( 'Add Credit Note', 'ihumbak-invoices' ); ?>
    </a>

    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ihumbak-invoices&action=new&type=receipt_return' ) ); ?>" class="page-title-action">
        <?php esc_html_e( 'Add Receipt Return', 'ihumbak-invoices' ); ?>
    </a>

    <hr class="wp-header-end">

    <?php
    // Display admin notices.
    // phpcs:disable WordPress.Security.NonceVerification.Recommended
    if ( isset( $_GET['message'] ) ) {
        $message = sanitize_text_field( wp_unslash( $_GET['message'] ) );

        $success_messages = [
            'saved'      => __( 'Document saved successfully.', 'ihumbak-invoices' ),
            'deleted'    => __( 'Document deleted successfully.', 'ihumbak-invoices' ),
            'email_sent' => __( 'Email sent successfully.', 'ihumbak-invoices' ),
        ];

        $error_messages = [
            'email_error' => __( 'Failed to send email. Please check the document has a linked order with a billing email.', 'ihumbak-invoices' ),
        ];

        if ( isset( $success_messages[ $message ] ) ) {
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html( $success_messages[ $message ] )
            );
        } elseif ( isset( $error_messages[ $message ] ) ) {
            printf(
                '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                esc_html( $error_messages[ $message ] )
            );
        }
    }
    // phpcs:enable WordPress.Security.NonceVerification.Recommended
    ?>

    <form method="get">
        <input type="hidden" name="page" value="ihumbak-invoices">

        <?php
        $list_table->search_box( __( 'Search', 'ihumbak-invoices' ), 'document' );
        $list_table->display();
        ?>
    </form>
</div>
