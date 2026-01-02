<?php
/**
 * AJAX Controller.
 *
 * @package IHumbak\Invoices\Modules\Admin
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Admin;

use IHumbak\Invoices\Modules\Invoice\CalculationService;
use IHumbak\Invoices\Modules\Invoice\NumberingService;
use IHumbak\Invoices\Modules\Invoice\OrderDataExtractor;
use IHumbak\Invoices\Modules\Invoice\RefundDataExtractor;
use IHumbak\Invoices\Infrastructure\Database\DocumentRepository;
use IHumbak\Invoices\Infrastructure\Database\DocumentItemRepository;
use IHumbak\Invoices\Core\Plugin;
use IHumbak\Invoices\Models\Receipt;

/**
 * Handles AJAX requests.
 */
class AjaxController {

	/**
	 * Calculation service.
	 *
	 * @var CalculationService
	 */
	private CalculationService $calculation_service;

	/**
	 * Numbering service.
	 *
	 * @var NumberingService
	 */
	private NumberingService $numbering_service;

	/**
	 * Order data extractor.
	 *
	 * @var OrderDataExtractor
	 */
	private OrderDataExtractor $order_extractor;

	/**
	 * Refund data extractor.
	 *
	 * @var RefundDataExtractor
	 */
	private RefundDataExtractor $refund_extractor;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->calculation_service = new CalculationService();
		$this->numbering_service   = new NumberingService();
		$this->order_extractor     = new OrderDataExtractor();
		$this->refund_extractor    = new RefundDataExtractor();
	}

	/**
	 * Initialize AJAX hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'wp_ajax_ihumbak_calculate_item', array( $this, 'calculate_item' ) );
		add_action( 'wp_ajax_ihumbak_calculate_document', array( $this, 'calculate_document' ) );
		add_action( 'wp_ajax_ihumbak_preview_number', array( $this, 'preview_number' ) );
		add_action( 'wp_ajax_ihumbak_fetch_order_data', array( $this, 'fetch_order_data' ) );
		add_action( 'wp_ajax_ihumbak_fetch_invoice_data', array( $this, 'fetch_invoice_data' ) );
		add_action( 'wp_ajax_ihumbak_fetch_receipt_data', array( $this, 'fetch_receipt_data' ) );
		add_action( 'wp_ajax_ihumbak_fetch_refund_data', array( $this, 'fetch_refund_data' ) );
	}

	/**
	 * Calculate single item values.
	 *
	 * @return void
	 */
	public function calculate_item(): void {
		check_ajax_referer( 'ihumbak_invoices_nonce', 'nonce' );

		if ( ! Plugin::get_instance()->getPermissionService()->canManageDocuments() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ihumbak-invoices' ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cast to float is sufficient sanitization.
		$quantity = (float) wp_unslash( $_POST['quantity'] ?? 1 );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cast to float is sufficient sanitization.
		$tax_rate   = (float) wp_unslash( $_POST['tax_rate'] ?? 23 );
		$price_type = sanitize_text_field( wp_unslash( $_POST['price_type'] ?? 'net' ) );

		if ( 'gross' === $price_type ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cast to float is sufficient sanitization.
			$unit_price = (float) wp_unslash( $_POST['unit_price_gross'] ?? 0 );
			$result     = $this->calculation_service->calculateFromGross( $unit_price, $quantity, $tax_rate );
		} else {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cast to float is sufficient sanitization.
			$unit_price = (float) wp_unslash( $_POST['unit_price_net'] ?? 0 );
			$result     = $this->calculation_service->calculateFromNet( $unit_price, $quantity, $tax_rate );
		}

		// Format values for display.
		$result['formatted'] = $this->format_item_values( $result );

		wp_send_json_success( $result );
	}

	/**
	 * Calculate entire document (all items + totals).
	 *
	 * @return void
	 */
	public function calculate_document(): void {
		check_ajax_referer( 'ihumbak_invoices_nonce', 'nonce' );

		if ( ! Plugin::get_instance()->getPermissionService()->canManageDocuments() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ihumbak-invoices' ) ) );
		}

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in sanitize_items_data method.
		$items_data = isset( $_POST['items'] ) && is_array( $_POST['items'] )
			? $this->sanitize_items_data( wp_unslash( $_POST['items'] ) )
			: array();
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( empty( $items_data ) ) {
			wp_send_json_success(
				array(
					'items'     => array(),
					'subtotal'  => 0,
					'tax_total' => 0,
					'total'     => 0,
					'formatted' => array(
						'subtotal'  => '0,00',
						'tax_total' => '0,00',
						'total'     => '0,00',
					),
				)
			);
		}

		$result = $this->calculation_service->calculateFromItemsData( $items_data );

		// Format totals.
		$result['formatted'] = array(
			'subtotal'  => number_format( $result['subtotal'], 2, ',', ' ' ),
			'tax_total' => number_format( $result['tax_total'], 2, ',', ' ' ),
			'total'     => number_format( $result['total'], 2, ',', ' ' ),
		);

		// Format item values.
		foreach ( $result['items'] as $index => $item ) {
			$result['items'][ $index ]['formatted'] = $this->format_item_values( $item );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Preview next document number.
	 *
	 * @return void
	 */
	public function preview_number(): void {
		check_ajax_referer( 'ihumbak_invoices_nonce', 'nonce' );

		if ( ! Plugin::get_instance()->getPermissionService()->canManageDocuments() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ihumbak-invoices' ) ) );
		}

		$document_type = sanitize_text_field( wp_unslash( $_POST['document_type'] ?? 'invoice' ) );
		$settings      = Plugin::get_instance()->get_settings();

		$pattern_key = $document_type . '_pattern';
		$pattern     = $settings['numbering'][ $pattern_key ] ?? NumberingService::getDefaultPattern( $document_type );

		$next_number = $this->numbering_service->previewNextNumber(
			$document_type,
			$pattern,
			$settings['numbering']['reset_monthly'] ?? true
		);

		wp_send_json_success( array( 'number' => $next_number ) );
	}

	/**
	 * Format item values for display.
	 *
	 * @param array<string, mixed> $item Item data with numeric values.
	 * @return array<string, string>
	 */
	private function format_item_values( array $item ): array {
		return array(
			'unit_price_net'   => number_format( (float) ( $item['unit_price_net'] ?? 0 ), 2, ',', ' ' ),
			'unit_price_gross' => number_format( (float) ( $item['unit_price_gross'] ?? 0 ), 2, ',', ' ' ),
			'tax_amount'       => number_format( (float) ( $item['tax_amount'] ?? 0 ), 2, ',', ' ' ),
			'line_total_net'   => number_format( (float) ( $item['line_total_net'] ?? 0 ), 2, ',', ' ' ),
			'line_total_gross' => number_format( (float) ( $item['line_total_gross'] ?? 0 ), 2, ',', ' ' ),
		);
	}

	/**
	 * Sanitize items data from request.
	 *
	 * @param array<int, array<string, mixed>> $items Raw items data.
	 * @return array<int, array<string, mixed>>
	 */
	private function sanitize_items_data( array $items ): array {
		$sanitized = array();

		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$sanitized[ $index ] = array(
				'name'             => sanitize_text_field( wp_unslash( $item['name'] ?? '' ) ),
				'quantity'         => (float) ( $item['quantity'] ?? 1 ),
				'unit'             => sanitize_text_field( wp_unslash( $item['unit'] ?? 'szt.' ) ),
				'unit_price_net'   => (float) ( $item['unit_price_net'] ?? 0 ),
				'unit_price_gross' => (float) ( $item['unit_price_gross'] ?? 0 ),
				'tax_rate'         => (float) ( $item['tax_rate'] ?? 23 ),
				'price_type'       => sanitize_text_field( wp_unslash( $item['price_type'] ?? 'net' ) ),
			);
		}

		return $sanitized;
	}

	/**
	 * Fetch order data via AJAX.
	 *
	 * @return void
	 */
	public function fetch_order_data(): void {
		check_ajax_referer( 'ihumbak_invoices_nonce', 'nonce' );

		if ( ! Plugin::get_instance()->getPermissionService()->canManageDocuments() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ihumbak-invoices' ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- absint sanitizes.
		$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;

		if ( ! $order_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid order ID.', 'ihumbak-invoices' ) ) );
		}

		// Check if WooCommerce is active.
		if ( ! function_exists( 'wc_get_order' ) ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce is not active.', 'ihumbak-invoices' ) ) );
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found.', 'ihumbak-invoices' ) ) );
		}

		// Get NIP meta key from settings.
		$settings     = Plugin::get_instance()->get_settings();
		$nip_meta_key = $settings['automation']['nip_meta_key'] ?? '_billing_nip';

		$data = $this->order_extractor->extractAll( $order, $nip_meta_key );

		// Add formatted values for display.
		foreach ( $data['items'] as $index => $item ) {
			$data['items'][ $index ]['formatted'] = $this->format_item_values( $item );
		}

		wp_send_json_success( $data );
	}

	/**
	 * Fetch invoice data for credit note creation.
	 *
	 * @return void
	 */
	public function fetch_invoice_data(): void {
		check_ajax_referer( 'ihumbak_invoices_nonce', 'nonce' );

		if ( ! Plugin::get_instance()->getPermissionService()->canManageDocuments() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ihumbak-invoices' ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- absint sanitizes.
		$invoice_id = isset( $_POST['invoice_id'] ) ? absint( wp_unslash( $_POST['invoice_id'] ) ) : 0;

		if ( ! $invoice_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid invoice ID.', 'ihumbak-invoices' ) ) );
		}

		$repository      = new DocumentRepository();
		$item_repository = new DocumentItemRepository();

		$invoice = $repository->find( $invoice_id );

		if ( ! $invoice ) {
			wp_send_json_error( array( 'message' => __( 'Invoice not found.', 'ihumbak-invoices' ) ) );
		}

		$items = $item_repository->findByDocumentId( $invoice_id );

		// Format items for response.
		$items_data = array();
		foreach ( $items as $item ) {
			$item_array              = $item->toArray();
			$item_array['formatted'] = $this->format_item_values( $item_array );
			$items_data[]            = $item_array;
		}

		// Get available refunds if order is linked.
		$refunds = array();
		if ( $invoice->getOrderId() ) {
			$refunds = $this->refund_extractor->extractRefundsFromOrderId( $invoice->getOrderId() );
		}

		wp_send_json_success(
			array(
				'invoice' => array(
					'id'              => $invoice->getId(),
					'document_number' => $invoice->getDocumentNumber(),
					'issue_date'      => $invoice->getIssueDate() ? $invoice->getIssueDate()->format( 'Y-m-d' ) : '',
					'order_id'        => $invoice->getOrderId(),
				),
				'buyer'   => $invoice->getBuyer() ? $invoice->getBuyer()->toArray() : array(),
				'seller'  => $invoice->getSeller() ? $invoice->getSeller()->toArray() : array(),
				'items'   => $items_data,
				'totals'  => array(
					'subtotal'  => $invoice->getSubtotal(),
					'tax_total' => $invoice->getTaxTotal(),
					'total'     => $invoice->getTotal(),
				),
				'refunds' => $refunds,
			)
		);
	}

	/**
	 * Fetch receipt data for receipt return creation.
	 *
	 * @return void
	 */
	public function fetch_receipt_data(): void {
		check_ajax_referer( 'ihumbak_invoices_nonce', 'nonce' );

		if ( ! Plugin::get_instance()->getPermissionService()->canManageDocuments() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ihumbak-invoices' ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- absint sanitizes.
		$receipt_id = isset( $_POST['receipt_id'] ) ? absint( wp_unslash( $_POST['receipt_id'] ) ) : 0;

		if ( ! $receipt_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid receipt ID.', 'ihumbak-invoices' ) ) );
		}

		$repository      = new DocumentRepository();
		$item_repository = new DocumentItemRepository();

		$receipt = $repository->find( $receipt_id );

		if ( ! $receipt || $receipt->getDocumentType() !== Receipt::TYPE ) {
			wp_send_json_error( array( 'message' => __( 'Receipt not found.', 'ihumbak-invoices' ) ) );
		}

		$items = $item_repository->findByDocumentId( $receipt_id );

		// Format items for response.
		$items_data = array();
		foreach ( $items as $item ) {
			$item_array              = $item->toArray();
			$item_array['formatted'] = $this->format_item_values( $item_array );
			$items_data[]            = $item_array;
		}

		// Get available refunds if order is linked.
		$refunds = array();
		if ( $receipt->getOrderId() ) {
			$refunds = $this->refund_extractor->extractRefundsFromOrderId( $receipt->getOrderId() );
		}

		wp_send_json_success(
			array(
				'receipt' => array(
					'id'              => $receipt->getId(),
					'document_number' => $receipt->getDocumentNumber(),
					'issue_date'      => $receipt->getIssueDate() ? $receipt->getIssueDate()->format( 'Y-m-d' ) : '',
					'order_id'        => $receipt->getOrderId(),
				),
				'buyer'   => $receipt->getBuyer() ? $receipt->getBuyer()->toArray() : array(),
				'seller'  => $receipt->getSeller() ? $receipt->getSeller()->toArray() : array(),
				'items'   => $items_data,
				'totals'  => array(
					'subtotal'  => $receipt->getSubtotal(),
					'tax_total' => $receipt->getTaxTotal(),
					'total'     => $receipt->getTotal(),
				),
				'refunds' => $refunds,
			)
		);
	}

	/**
	 * Fetch refund data for credit note pre-filling.
	 *
	 * @return void
	 */
	public function fetch_refund_data(): void {
		check_ajax_referer( 'ihumbak_invoices_nonce', 'nonce' );

		if ( ! Plugin::get_instance()->getPermissionService()->canManageDocuments() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ihumbak-invoices' ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- absint sanitizes.
		$refund_id = isset( $_POST['refund_id'] ) ? absint( wp_unslash( $_POST['refund_id'] ) ) : 0;

		if ( ! $refund_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid refund ID.', 'ihumbak-invoices' ) ) );
		}

		$refund_data = $this->refund_extractor->extractRefundById( $refund_id );

		if ( ! $refund_data ) {
			wp_send_json_error( array( 'message' => __( 'Refund not found.', 'ihumbak-invoices' ) ) );
		}

		wp_send_json_success( $refund_data );
	}
}
