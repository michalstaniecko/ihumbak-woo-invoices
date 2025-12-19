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
use IHumbak\Invoices\Core\Plugin;

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
	 * Constructor.
	 */
	public function __construct() {
		$this->calculation_service = new CalculationService();
		$this->numbering_service   = new NumberingService();
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
	}

	/**
	 * Calculate single item values.
	 *
	 * @return void
	 */
	public function calculate_item(): void {
		check_ajax_referer( 'ihumbak_invoices_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
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
		$result['formatted'] = array(
			'unit_price_net'   => number_format( $result['unit_price_net'], 2, ',', ' ' ),
			'unit_price_gross' => number_format( $result['unit_price_gross'], 2, ',', ' ' ),
			'tax_amount'       => number_format( $result['tax_amount'], 2, ',', ' ' ),
			'line_total_net'   => number_format( $result['line_total_net'], 2, ',', ' ' ),
			'line_total_gross' => number_format( $result['line_total_gross'], 2, ',', ' ' ),
		);

		wp_send_json_success( $result );
	}

	/**
	 * Calculate entire document (all items + totals).
	 *
	 * @return void
	 */
	public function calculate_document(): void {
		check_ajax_referer( 'ihumbak_invoices_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
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
			$result['items'][ $index ]['formatted'] = array(
				'unit_price_net'   => number_format( $item['unit_price_net'], 2, ',', ' ' ),
				'unit_price_gross' => number_format( $item['unit_price_gross'], 2, ',', ' ' ),
				'tax_amount'       => number_format( $item['tax_amount'], 2, ',', ' ' ),
				'line_total_net'   => number_format( $item['line_total_net'], 2, ',', ' ' ),
				'line_total_gross' => number_format( $item['line_total_gross'], 2, ',', ' ' ),
			);
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

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
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
}
