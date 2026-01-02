<?php
/**
 * Order MetaBox.
 *
 * Displays invoice/receipt actions on WooCommerce order edit page.
 *
 * @package IHumbak\Invoices\Modules\Admin
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Admin;

use IHumbak\Invoices\Infrastructure\Database\DocumentRepository;
use IHumbak\Invoices\Models\Document;
use IHumbak\Invoices\Core\Plugin;

/**
 * Order MetaBox class.
 */
class OrderMetaBox {

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
		// Legacy WooCommerce orders (post type: shop_order).
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );

		// HPOS WooCommerce orders.
		add_action( 'add_meta_boxes_woocommerce_page_wc-orders', array( $this, 'register_meta_box' ) );
	}

	/**
	 * Register the meta box.
	 *
	 * @return void
	 */
	public function register_meta_box(): void {
		if ( ! Plugin::get_instance()->getPermissionService()->canManageDocuments() ) {
			return;
		}

		$screens = $this->get_order_screens();

		foreach ( $screens as $screen ) {
			add_meta_box(
				'ihumbak-invoices-order-metabox',
				__( 'Invoices', 'ihumbak-invoices' ),
				array( $this, 'render_meta_box' ),
				$screen,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render the meta box content.
	 *
	 * @param \WP_Post|\WC_Order $post_or_order Post or Order object.
	 * @return void
	 */
	public function render_meta_box( $post_or_order ): void {
		$order_id = $this->get_order_id_from_param( $post_or_order );

		if ( ! $order_id ) {
			echo '<p>' . esc_html__( 'Order not found.', 'ihumbak-invoices' ) . '</p>';
			return;
		}

		$documents = $this->repository->findByOrderId( $order_id );

		// Build URLs for template.
		$create_invoice_url = $this->build_create_url( $order_id, 'invoice' );
		$create_receipt_url = $this->build_create_url( $order_id, 'receipt' );

		$edit_urls           = array();
		$pdf_urls            = array();
		$credit_note_urls    = array();
		$receipt_return_urls = array();

		foreach ( $documents as $document ) {
			$doc_id               = $document->getId();
			$edit_urls[ $doc_id ] = $this->build_edit_url( $document );
			$pdf_urls[ $doc_id ]  = $this->build_pdf_url( $document );

			// Build credit note URL for issued invoices only.
			if ( 'invoice' === $document->getDocumentType() && ! $document->isDraft() ) {
				$credit_note_urls[ $doc_id ] = $this->build_create_credit_note_url( $order_id, $doc_id );
			}

			// Build receipt return URL for issued receipts only.
			if ( 'receipt' === $document->getDocumentType() && ! $document->isDraft() ) {
				$receipt_return_urls[ $doc_id ] = $this->build_create_receipt_return_url( $order_id, $doc_id );
			}
		}

		include IHUMBAK_INVOICES_PATH . 'templates/admin/metabox/order-invoices.php';
	}

	/**
	 * Get order ID from metabox callback parameter.
	 *
	 * @param \WP_Post|\WC_Order|mixed $post_or_order Post or Order object.
	 * @return int|null Order ID or null.
	 */
	private function get_order_id_from_param( $post_or_order ): ?int {
		// HPOS: WC_Order object.
		if ( $post_or_order instanceof \WC_Order ) {
			return $post_or_order->get_id();
		}

		// Legacy: WP_Post object.
		if ( $post_or_order instanceof \WP_Post ) {
			return $post_or_order->ID;
		}

		// Numeric ID.
		if ( is_numeric( $post_or_order ) ) {
			return (int) $post_or_order;
		}

		return null;
	}

	/**
	 * Get order screen types for metabox registration.
	 *
	 * @return array<string> Screen types.
	 */
	private function get_order_screens(): array {
		$screens = array( 'shop_order' );

		if ( $this->is_hpos_enabled() ) {
			$screens[] = 'woocommerce_page_wc-orders';
		}

		return $screens;
	}

	/**
	 * Check if HPOS is enabled.
	 *
	 * @return bool
	 */
	private function is_hpos_enabled(): bool {
		if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
			return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		}

		return false;
	}

	/**
	 * Build URL for creating a new document with order pre-filled.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $type     Document type (invoice or receipt).
	 * @return string
	 */
	private function build_create_url( int $order_id, string $type ): string {
		return add_query_arg(
			array(
				'page'     => 'ihumbak-invoices',
				'action'   => 'new',
				'type'     => $type,
				'order_id' => $order_id,
				'_wpnonce' => wp_create_nonce( 'ihumbak_create_from_order_' . $order_id ),
			),
			admin_url( 'admin.php' )
		);
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

	/**
	 * Build URL for downloading PDF.
	 *
	 * @param Document $document Document.
	 * @return string
	 */
	private function build_pdf_url( Document $document ): string {
		return add_query_arg(
			array(
				'page'   => 'ihumbak-invoices',
				'action' => 'pdf',
				'id'     => $document->getId(),
				'nonce'  => wp_create_nonce( 'pdf_document_' . $document->getId() ),
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Build URL for creating a credit note from an invoice.
	 *
	 * Uses nonce pattern: ihumbak_create_credit_note_{invoice_id}
	 * This pattern is consistent with invoice-edit.php template.
	 *
	 * @param int $order_id   Order ID (passed for refunds loading).
	 * @param int $invoice_id Invoice ID to correct.
	 * @return string
	 */
	private function build_create_credit_note_url( int $order_id, int $invoice_id ): string {
		return add_query_arg(
			array(
				'page'                  => 'ihumbak-invoices',
				'action'                => 'new',
				'type'                  => 'credit_note',
				'corrected_document_id' => $invoice_id,
				'order_id'              => $order_id,
				'_wpnonce'              => wp_create_nonce( 'ihumbak_create_credit_note_' . $invoice_id ),
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Build URL for creating a receipt return from a receipt.
	 *
	 * Uses nonce pattern: ihumbak_create_receipt_return_{receipt_id}
	 *
	 * @param int $order_id   Order ID (passed for refunds loading).
	 * @param int $receipt_id Receipt ID to return.
	 * @return string
	 */
	private function build_create_receipt_return_url( int $order_id, int $receipt_id ): string {
		return add_query_arg(
			array(
				'page'                  => 'ihumbak-invoices',
				'action'                => 'new',
				'type'                  => 'receipt_return',
				'corrected_document_id' => $receipt_id,
				'order_id'              => $order_id,
				'_wpnonce'              => wp_create_nonce( 'ihumbak_create_receipt_return_' . $receipt_id ),
			),
			admin_url( 'admin.php' )
		);
	}
}
