<?php
/**
 * Order List Column.
 *
 * Adds invoice documents column to WooCommerce orders list.
 *
 * @package IHumbak\Invoices\Modules\Admin
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Admin;

use IHumbak\Invoices\Infrastructure\Database\DocumentRepository;
use IHumbak\Invoices\Models\Document;

/**
 * Order List Column class.
 *
 * Displays invoice/receipt documents in WooCommerce orders list table.
 */
class OrderListColumn {

	/**
	 * Column identifier.
	 */
	private const COLUMN_ID = 'ihumbak_documents';

	/**
	 * Maximum length for short document numbers (displayed without shortening).
	 */
	private const MAX_SHORT_NUMBER_LENGTH = 15;

	/**
	 * Document repository.
	 *
	 * @var DocumentRepository
	 */
	private DocumentRepository $repository;

	/**
	 * Constructor.
	 *
	 * @param DocumentRepository $repository Document repository.
	 */
	public function __construct( DocumentRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Check if feature is enabled in settings.
		if ( ! $this->is_enabled() ) {
			return;
		}

		// Legacy WooCommerce (CPT: shop_order).
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_column' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_column_legacy' ), 10, 2 );

		// HPOS WooCommerce.
		add_filter( 'woocommerce_shop_order_list_table_columns', array( $this, 'add_column' ) );
		add_action( 'woocommerce_shop_order_list_table_custom_column', array( $this, 'render_column_hpos' ), 10, 2 );
	}

	/**
	 * Check if this feature is enabled in settings.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		$settings = get_option( 'ihumbak_invoices_settings', array() );

		// Default to true if not set.
		if ( ! isset( $settings['display']['show_order_column'] ) ) {
			return true;
		}

		return ! empty( $settings['display']['show_order_column'] );
	}

	/**
	 * Add column to orders list.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string>
	 */
	public function add_column( array $columns ): array {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			// Insert after order_status column.
			if ( 'order_status' === $key ) {
				$new_columns[ self::COLUMN_ID ] = __( 'Documents', 'ihumbak-invoices' );
			}
		}

		// Fallback if order_status column not found.
		if ( ! isset( $new_columns[ self::COLUMN_ID ] ) ) {
			$new_columns[ self::COLUMN_ID ] = __( 'Documents', 'ihumbak-invoices' );
		}

		return $new_columns;
	}

	/**
	 * Render column for Legacy (CPT) orders.
	 *
	 * @param string $column  Column ID.
	 * @param int    $post_id Post (order) ID.
	 * @return void
	 */
	public function render_column_legacy( string $column, int $post_id ): void {
		if ( self::COLUMN_ID !== $column ) {
			return;
		}

		$this->render_documents( $post_id );
	}

	/**
	 * Render column for HPOS orders.
	 *
	 * @param string    $column Column ID.
	 * @param \WC_Order $order  Order object.
	 * @return void
	 */
	public function render_column_hpos( string $column, \WC_Order $order ): void {
		if ( self::COLUMN_ID !== $column ) {
			return;
		}

		$this->render_documents( $order->get_id() );
	}

	/**
	 * Render documents for an order.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	private function render_documents( int $order_id ): void {
		// TODO: Consider batch loading for large order lists to avoid N+1 queries.
		$documents = $this->repository->findByOrderId( $order_id );

		if ( empty( $documents ) ) {
			echo '<span class="ihumbak-no-docs">&mdash;</span>';
			return;
		}

		$output = array();

		foreach ( $documents as $document ) {
			$output[] = $this->format_document_link( $document );
		}

		// Display documents, each on new line.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Links escaped in format_document_link.
		echo implode( '<br>', $output );
	}

	/**
	 * Format document as link.
	 *
	 * @param Document $document Document.
	 * @return string HTML link.
	 */
	private function format_document_link( Document $document ): string {
		$edit_url = $this->build_edit_url( $document );
		$number   = $document->getDocumentNumber();
		$type     = $document->getDocumentType();

		// Icon for document type.
		$icon = $this->get_document_icon( $type );

		// Display number or draft label.
		$display_number = $number ?: __( '(Draft)', 'ihumbak-invoices' );

		// CSS class for document type and status.
		$status_class = 'ihumbak-doc-link ihumbak-doc-' . esc_attr( $type );
		if ( $document->isDraft() ) {
			$status_class .= ' ihumbak-doc-draft';
		}

		// Build tooltip.
		$tooltip = $this->get_document_type_label( $type ) . ': ' . $display_number;

		return sprintf(
			'<a href="%s" class="%s" title="%s">%s%s</a>',
			esc_url( $edit_url ),
			esc_attr( $status_class ),
			esc_attr( $tooltip ),
			$icon,
			esc_html( $this->shorten_number( $display_number ) )
		);
	}

	/**
	 * Get icon for document type.
	 *
	 * @param string $type Document type.
	 * @return string HTML icon.
	 */
	private function get_document_icon( string $type ): string {
		$icons = array(
			'invoice'     => '<span class="dashicons dashicons-media-document ihumbak-doc-icon"></span>',
			'receipt'     => '<span class="dashicons dashicons-media-text ihumbak-doc-icon"></span>',
			'credit_note' => '<span class="dashicons dashicons-undo ihumbak-doc-icon"></span>',
		);

		return $icons[ $type ] ?? '';
	}

	/**
	 * Get human-readable label for document type.
	 *
	 * @param string $type Document type.
	 * @return string
	 */
	private function get_document_type_label( string $type ): string {
		$labels = array(
			'invoice'     => __( 'Invoice', 'ihumbak-invoices' ),
			'receipt'     => __( 'Receipt', 'ihumbak-invoices' ),
			'credit_note' => __( 'Credit Note', 'ihumbak-invoices' ),
		);

		return $labels[ $type ] ?? $type;
	}

	/**
	 * Shorten document number for display.
	 *
	 * Removes year/month prefix for cleaner display.
	 * Example: "FV/2025/01/0001" -> "FV/.../0001"
	 *
	 * @param string $number Full document number.
	 * @return string Shortened number.
	 */
	private function shorten_number( string $number ): string {
		// If number is short, display full.
		if ( mb_strlen( $number ) <= self::MAX_SHORT_NUMBER_LENGTH ) {
			return $number;
		}

		// Try to shorten pattern like FV/2025/01/0001.
		if ( preg_match( '/^([A-Z]+)\/\d{4}\/\d{2}\/(\d+)$/', $number, $matches ) ) {
			return $matches[1] . '/.../' . $matches[2];
		}

		// If no match, truncate and add ellipsis.
		return mb_substr( $number, 0, 12 ) . '...';
	}

	/**
	 * Build URL for editing a document.
	 *
	 * @param Document $document Document.
	 * @return string
	 */
	private function build_edit_url( Document $document ): string {
		return add_query_arg(
			array(
				'page'   => 'ihumbak-invoices',
				'action' => 'edit',
				'type'   => $document->getDocumentType(),
				'id'     => $document->getId(),
			),
			admin_url( 'admin.php' )
		);
	}
}
