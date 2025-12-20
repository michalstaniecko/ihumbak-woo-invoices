<?php
/**
 * Refund Data Extractor Service.
 *
 * @package IHumbak\Invoices\Modules\Invoice
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Invoice;

/**
 * Service for extracting refund data from WooCommerce orders.
 */
class RefundDataExtractor {

	/**
	 * Extract refunds data from a WooCommerce order.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return array<int, array<string, mixed>> Array of refund data.
	 */
	public function extractRefundsFromOrderId( int $order_id ): array {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return array();
		}

		$order = wc_get_order( $order_id );

		if ( ! $order || $order instanceof \WC_Order_Refund ) {
			return array();
		}

		return $this->extractRefundsFromOrder( $order );
	}

	/**
	 * Extract refunds data from a WooCommerce order object.
	 *
	 * @param \WC_Order $order WooCommerce order.
	 * @return array<int, array<string, mixed>> Array of refund data.
	 */
	public function extractRefundsFromOrder( \WC_Order $order ): array {
		$refunds = array();

		foreach ( $order->get_refunds() as $refund ) {
			$refund_items = $this->extractRefundItems( $refund );

			$refunds[] = array(
				'id'     => $refund->get_id(),
				'amount' => floatval( $refund->get_amount() ),
				'reason' => $refund->get_reason(),
				'date'   => $refund->get_date_created() ? $refund->get_date_created()->format( 'Y-m-d' ) : '',
				'items'  => $refund_items,
			);
		}

		return $refunds;
	}

	/**
	 * Extract single refund data by ID.
	 *
	 * @param int $refund_id WooCommerce refund ID.
	 * @return array<string, mixed>|null Refund data or null if not found.
	 */
	public function extractRefundById( int $refund_id ): ?array {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return null;
		}

		$refund = wc_get_order( $refund_id );

		if ( ! $refund instanceof \WC_Order_Refund ) {
			return null;
		}

		$items = array();
		foreach ( $refund->get_items( 'line_item' ) as $item ) {
			$refunded_item_id = $item->get_meta( '_refunded_item_id' );
			$quantity         = abs( $item->get_quantity() );
			$total            = abs( floatval( $item->get_total() ) );
			$tax              = abs( floatval( $item->get_total_tax() ) );

			$items[] = array(
				'original_item_id' => $refunded_item_id,
				'name'             => $item->get_name(),
				'quantity'         => $quantity,
				'unit_price_net'   => $quantity > 0 ? round( $total / $quantity, 2 ) : 0,
				'total'            => $total,
				'tax'              => $tax,
			);
		}

		return array(
			'refund_id' => $refund_id,
			'reason'    => $refund->get_reason(),
			'amount'    => floatval( $refund->get_amount() ),
			'items'     => $items,
		);
	}

	/**
	 * Extract items from a refund.
	 *
	 * @param \WC_Order_Refund $refund WooCommerce refund.
	 * @return array<int, array<string, mixed>> Refund items data.
	 */
	private function extractRefundItems( \WC_Order_Refund $refund ): array {
		$items = array();

		foreach ( $refund->get_items( 'line_item' ) as $item ) {
			$items[] = array(
				'name'             => $item->get_name(),
				'quantity'         => abs( $item->get_quantity() ),
				'total'            => abs( floatval( $item->get_total() ) ),
				'tax'              => abs( floatval( $item->get_total_tax() ) ),
				'original_item_id' => $item->get_meta( '_refunded_item_id' ),
			);
		}

		return $items;
	}
}
