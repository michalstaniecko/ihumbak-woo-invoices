<?php
/**
 * Portal Controller.
 *
 * Handles WooCommerce My Account portal integration for customer document access.
 *
 * @package IHumbak\Invoices\Modules\Portal
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Portal;

use IHumbak\Invoices\Infrastructure\Database\DocumentRepository;
use IHumbak\Invoices\Infrastructure\Database\DocumentItemRepository;
use IHumbak\Invoices\Modules\PDF\PdfGenerator;
use IHumbak\Invoices\Models\Document;

/**
 * Controller for customer portal in WooCommerce My Account.
 */
class PortalController {

	/**
	 * Endpoint slug for My Account.
	 */
	public const ENDPOINT_SLUG = 'invoices';

	/**
	 * Download action name.
	 */
	public const DOWNLOAD_ACTION = 'ihumbak_customer_download_pdf';

	/**
	 * Nonce action prefix.
	 */
	private const NONCE_PREFIX = 'ihumbak_customer_pdf_';

	/**
	 * Portal service.
	 *
	 * @var PortalService
	 */
	private PortalService $portal_service;

	/**
	 * Document repository.
	 *
	 * @var DocumentRepository
	 */
	private DocumentRepository $document_repository;

	/**
	 * Constructor.
	 *
	 * @param PortalService|null      $portal_service      Portal service instance.
	 * @param DocumentRepository|null $document_repository Document repository instance.
	 */
	public function __construct(
		?PortalService $portal_service = null,
		?DocumentRepository $document_repository = null
	) {
		$this->portal_service      = $portal_service ?? new PortalService();
		$this->document_repository = $document_repository ?? new DocumentRepository();
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Register endpoint.
		add_action( 'init', array( $this, 'register_endpoint' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );

		// Menu and content.
		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu_item' ) );
		add_action( 'woocommerce_account_' . self::ENDPOINT_SLUG . '_endpoint', array( $this, 'render_documents_list' ) );

		// PDF download handler (early, before any output).
		add_action( 'template_redirect', array( $this, 'handle_pdf_download' ), 1 );

		// Order details integration.
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'render_order_documents' ), 20 );
	}

	/**
	 * Register the endpoint.
	 *
	 * @return void
	 */
	public function register_endpoint(): void {
		add_rewrite_endpoint( self::ENDPOINT_SLUG, EP_ROOT | EP_PAGES );
	}

	/**
	 * Add query vars.
	 *
	 * @param array<string> $vars Query variables.
	 * @return array<string>
	 */
	public function add_query_vars( array $vars ): array {
		$vars[] = self::ENDPOINT_SLUG;
		return $vars;
	}

	/**
	 * Add menu item to My Account navigation.
	 *
	 * @param array<string, string> $items Menu items.
	 * @return array<string, string>
	 */
	public function add_menu_item( array $items ): array {
		// Insert before 'customer-logout'.
		$new_items = array();

		foreach ( $items as $key => $label ) {
			if ( 'customer-logout' === $key ) {
				$new_items[ self::ENDPOINT_SLUG ] = __( 'Invoices', 'ihumbak-invoices' );
			}
			$new_items[ $key ] = $label;
		}

		// If 'customer-logout' doesn't exist, add at the end.
		if ( ! isset( $new_items[ self::ENDPOINT_SLUG ] ) ) {
			$new_items[ self::ENDPOINT_SLUG ] = __( 'Invoices', 'ihumbak-invoices' );
		}

		return $new_items;
	}

	/**
	 * Render documents list.
	 *
	 * @return void
	 */
	public function render_documents_list(): void {
		if ( ! is_user_logged_in() ) {
			wc_print_notice( __( 'Please log in to view your invoices.', 'ihumbak-invoices' ), 'notice' );
			return;
		}

		$customer_id = get_current_user_id();
		$documents   = $this->portal_service->getDocumentsForCustomer( $customer_id );

		// Build URLs for template.
		$download_urls = array();
		$order_urls    = array();

		foreach ( $documents as $document ) {
			$doc_id                   = $document->getId();
			$download_urls[ $doc_id ] = $this->get_download_url( $document );

			$order_id = $document->getOrderId();
			if ( $order_id && ! isset( $order_urls[ $order_id ] ) ) {
				$order_urls[ $order_id ] = wc_get_endpoint_url( 'view-order', (string) $order_id, wc_get_page_permalink( 'myaccount' ) );
			}
		}

		include IHUMBAK_INVOICES_PATH . 'templates/frontend/portal/documents-list.php';
	}

	/**
	 * Handle PDF download request.
	 *
	 * @return void
	 */
	public function handle_pdf_download(): void {
		// Check if this is a download request.
		if ( ! isset( $_GET['action'] ) || self::DOWNLOAD_ACTION !== $_GET['action'] ) {
			return;
		}

		// Verify user is logged in.
		if ( ! is_user_logged_in() ) {
			wp_die(
				esc_html__( 'You must be logged in to download invoices.', 'ihumbak-invoices' ),
				esc_html__( 'Access Denied', 'ihumbak-invoices' ),
				array( 'response' => 403 )
			);
		}

		// Get and validate document ID.
		$document_id = isset( $_GET['document_id'] ) ? absint( $_GET['document_id'] ) : 0;

		if ( ! $document_id ) {
			wp_die(
				esc_html__( 'Invalid document ID.', 'ihumbak-invoices' ),
				esc_html__( 'Error', 'ihumbak-invoices' ),
				array( 'response' => 400 )
			);
		}

		// Verify nonce.
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_PREFIX . $document_id ) ) {
			wp_die(
				esc_html__( 'Security check failed. Please try again.', 'ihumbak-invoices' ),
				esc_html__( 'Security Error', 'ihumbak-invoices' ),
				array( 'response' => 403 )
			);
		}

		// Verify customer has access to this document.
		$customer_id = get_current_user_id();
		if ( ! $this->portal_service->canCustomerAccessDocument( $customer_id, $document_id ) ) {
			wp_die(
				esc_html__( 'You do not have permission to download this document.', 'ihumbak-invoices' ),
				esc_html__( 'Access Denied', 'ihumbak-invoices' ),
				array( 'response' => 403 )
			);
		}

		// Get document and generate PDF.
		$document = $this->document_repository->find( $document_id );

		if ( ! $document ) {
			wp_die(
				esc_html__( 'Document not found.', 'ihumbak-invoices' ),
				esc_html__( 'Error', 'ihumbak-invoices' ),
				array( 'response' => 404 )
			);
		}

		// Load document items.
		$item_repository = new DocumentItemRepository();
		$items           = $item_repository->findByDocumentId( $document_id );
		$document->setItems( $items );

		// Generate and output PDF.
		$pdf_generator = new PdfGenerator();
		$pdf_generator->download( $document );

		exit;
	}

	/**
	 * Render documents section on order details page.
	 *
	 * @param \WC_Order $order WooCommerce order.
	 * @return void
	 */
	public function render_order_documents( \WC_Order $order ): void {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$customer_id = get_current_user_id();

		// Verify customer owns this order.
		if ( (int) $order->get_customer_id() !== $customer_id ) {
			return;
		}

		$documents = $this->portal_service->getDocumentsForOrder( $order->get_id() );

		if ( empty( $documents ) ) {
			return;
		}

		// Build download URLs.
		$download_urls = array();
		foreach ( $documents as $document ) {
			$doc_id                   = $document->getId();
			$download_urls[ $doc_id ] = $this->get_download_url( $document );
		}

		include IHUMBAK_INVOICES_PATH . 'templates/frontend/portal/order-documents.php';
	}

	/**
	 * Get download URL for a document.
	 *
	 * Uses base My Account URL to avoid rewrite rules dependency.
	 *
	 * @param Document $document Document.
	 * @return string
	 */
	public function get_download_url( Document $document ): string {
		$document_id = $document->getId();

		return add_query_arg(
			array(
				'action'      => self::DOWNLOAD_ACTION,
				'document_id' => $document_id,
				'_wpnonce'    => wp_create_nonce( self::NONCE_PREFIX . $document_id ),
			),
			wc_get_page_permalink( 'myaccount' )
		);
	}
}
