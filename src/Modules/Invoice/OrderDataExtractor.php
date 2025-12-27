<?php
/**
 * Order Data Extractor Service.
 *
 * @package IHumbak\Invoices\Modules\Invoice
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Invoice;

use WC_Order;
use WC_Order_Item_Product;

/**
 * Service for extracting data from WooCommerce orders.
 */
class OrderDataExtractor {

	/**
	 * Default payment method mapping from WooCommerce to invoice payment methods.
	 *
	 * @var array<string, string>
	 */
	private const DEFAULT_PAYMENT_METHOD_MAP = array(
		'bacs'       => 'transfer',
		'cod'        => 'cash',
		'cheque'     => 'transfer',
		'stripe'     => 'card',
		'stripe_cc'  => 'card',
		'paypal'     => 'online',
		'przelewy24' => 'online',
		'tpay'       => 'online',
		'payu'       => 'online',
		'dotpay'     => 'online',
	);

	/**
	 * Default unit for items.
	 *
	 * @var string
	 */
	private const DEFAULT_UNIT = 'szt.';

	/**
	 * Calculation service.
	 *
	 * @var CalculationService
	 */
	private CalculationService $calculation_service;

	/**
	 * Constructor.
	 *
	 * @param CalculationService|null $calculation_service Optional calculation service instance.
	 */
	public function __construct( ?CalculationService $calculation_service = null ) {
		$this->calculation_service = $calculation_service ?? new CalculationService();
	}

	/**
	 * Extract all data from WooCommerce order.
	 *
	 * @param WC_Order $order        WooCommerce order.
	 * @param string   $nip_meta_key Meta key for NIP field.
	 * @return array{items: array<int, array<string, mixed>>, buyer: array<string, string>, payment_method: array{type: string, id: string, title: string}, currency: string}
	 */
	public function extractAll( WC_Order $order, string $nip_meta_key = '_billing_nip' ): array {
		$items = $this->extractItems( $order );

		// Add shipping as item if applicable.
		$shipping = $this->extractShipping( $order );
		if ( null !== $shipping ) {
			$items[] = $shipping;
		}

		return array(
			'items'          => $items,
			'buyer'          => $this->extractBuyer( $order, $nip_meta_key ),
			'payment_method' => $this->extractPaymentMethod( $order ),
			'payment_date'   => $this->extractPaymentDate( $order ),
			'currency'       => $order->get_currency(),
		);
	}

	/**
	 * Extract product items from order.
	 *
	 * @param WC_Order $order WooCommerce order.
	 * @return array<int, array<string, mixed>>
	 */
	public function extractItems( WC_Order $order ): array {
		$items = array();

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$items[] = $this->extractItemData( $item );
		}

		return $items;
	}

	/**
	 * Extract data from single order item.
	 *
	 * @param WC_Order_Item_Product $item Order item.
	 * @return array<string, mixed>
	 */
	private function extractItemData( WC_Order_Item_Product $item ): array {
		$quantity = (float) $item->get_quantity();
		if ( $quantity <= 0 ) {
			$quantity = 1.0;
		}

		// Get line totals from WooCommerce (these are net values).
		$line_total_net = (float) $item->get_subtotal();
		$tax_amount     = (float) $item->get_subtotal_tax();

		// Calculate unit price net.
		$unit_price_net = $line_total_net / $quantity;

		// Calculate tax rate from amounts.
		$tax_rate = $this->calculateTaxRate( $line_total_net, $tax_amount );

		// Calculate gross values using calculation service.
		$calculated = $this->calculation_service->calculateFromNet( $unit_price_net, $quantity, $tax_rate );

		// Get SKU from product.
		$sku        = '';
		$product    = $item->get_product();
		$product_id = $item->get_product_id() ?: null;
		if ( $product ) {
			$sku = $product->get_sku();
		}

		return array(
			'product_id'       => $product_id,
			'name'             => $item->get_name(),
			'sku'              => $sku,
			'quantity'         => $quantity,
			'unit'             => $this->getItemUnit( $product_id, $item ),
			'unit_price_net'   => $calculated['unit_price_net'],
			'unit_price_gross' => $calculated['unit_price_gross'],
			'tax_rate'         => $calculated['tax_rate'],
			'tax_amount'       => $calculated['tax_amount'],
			'line_total_net'   => $calculated['line_total_net'],
			'line_total_gross' => $calculated['line_total_gross'],
		);
	}

	/**
	 * Extract shipping as item.
	 *
	 * @param WC_Order $order WooCommerce order.
	 * @return array<string, mixed>|null Item data or null if no shipping.
	 */
	public function extractShipping( WC_Order $order ): ?array {
		$shipping_total = (float) $order->get_shipping_total();

		// No shipping or free shipping.
		if ( $shipping_total <= 0 ) {
			return null;
		}

		$shipping_tax = (float) $order->get_shipping_tax();
		$tax_rate     = $this->calculateTaxRate( $shipping_total, $shipping_tax );

		// Calculate values using calculation service.
		$calculated = $this->calculation_service->calculateFromNet( $shipping_total, 1.0, $tax_rate );

		return array(
			'product_id'       => null,
			'name'             => $order->get_shipping_method() ?: __( 'Shipping', 'ihumbak-invoices' ),
			'sku'              => '',
			'quantity'         => 1.0,
			'unit'             => $this->getShippingUnit( $order ),
			'unit_price_net'   => $calculated['unit_price_net'],
			'unit_price_gross' => $calculated['unit_price_gross'],
			'tax_rate'         => $calculated['tax_rate'],
			'tax_amount'       => $calculated['tax_amount'],
			'line_total_net'   => $calculated['line_total_net'],
			'line_total_gross' => $calculated['line_total_gross'],
		);
	}

	/**
	 * Extract buyer data from order billing info.
	 *
	 * @param WC_Order $order        WooCommerce order.
	 * @param string   $nip_meta_key Meta key for NIP field.
	 * @return array<string, string>
	 */
	public function extractBuyer( WC_Order $order, string $nip_meta_key = '_billing_nip' ): array {
		$company    = $order->get_billing_company();
		$first_name = $order->get_billing_first_name();
		$last_name  = $order->get_billing_last_name();

		// Use company name if available, otherwise use personal name.
		$name = $company ?: trim( $first_name . ' ' . $last_name );

		// Build address.
		$address_1 = $order->get_billing_address_1();
		$address_2 = $order->get_billing_address_2();
		$address   = trim( $address_1 . ( $address_2 ? ' ' . $address_2 : '' ) );

		// Get NIP from order meta.
		$nip = '';
		if ( ! empty( $nip_meta_key ) ) {
			$nip_value = $order->get_meta( $nip_meta_key, true );
			if ( is_string( $nip_value ) ) {
				$nip = $nip_value;
			}
		}

		return array(
			'name'     => $name,
			'address'  => $address,
			'postcode' => $order->get_billing_postcode(),
			'city'     => $order->get_billing_city(),
			'country'  => $order->get_billing_country() ?: 'PL',
			'nip'      => $nip,
			'email'    => $order->get_billing_email(),
			'phone'    => $order->get_billing_phone(),
		);
	}

	/**
	 * Extract payment method data from WooCommerce order.
	 *
	 * Returns an array with:
	 * - type: Mapped payment type (transfer, cash, card, online)
	 * - id: Original WooCommerce payment method ID (e.g., 'bacs', 'przelewy24')
	 * - title: Human-readable payment method title from WooCommerce
	 *
	 * @param WC_Order $order WooCommerce order.
	 * @return array{type: string, id: string, title: string}
	 */
	public function extractPaymentMethod( WC_Order $order ): array {
		$payment_method_id    = $order->get_payment_method();
		$payment_method_title = $order->get_payment_method_title();

		if ( empty( $payment_method_id ) ) {
			return array(
				'type'  => '',
				'id'    => '',
				'title' => '',
			);
		}

		$payment_map = $this->getPaymentMethodMap();
		$type        = 'online'; // Default for unknown gateways.

		// Direct mapping.
		if ( isset( $payment_map[ $payment_method_id ] ) ) {
			$type = $payment_map[ $payment_method_id ];
		} else {
			// Check for partial matches (e.g., stripe_sepa -> card).
			foreach ( $payment_map as $key => $value ) {
				if ( str_starts_with( $payment_method_id, $key ) ) {
					$type = $value;
					break;
				}
			}
		}

		return array(
			'type'  => $type,
			'id'    => $payment_method_id,
			'title' => $payment_method_title,
		);
	}

	/**
	 * Extract payment date from WooCommerce order.
	 *
	 * Returns the date when the order was paid, or null if not paid.
	 *
	 * @param WC_Order $order WooCommerce order.
	 * @return string|null Date in Y-m-d format or null if not paid.
	 */
	public function extractPaymentDate( WC_Order $order ): ?string {
		$date_paid = $order->get_date_paid();

		if ( ! $date_paid ) {
			return null;
		}

		return $date_paid->format( 'Y-m-d' );
	}

	/**
	 * Get item unit with filter support.
	 *
	 * @param int|null                   $product_id Product ID if available.
	 * @param WC_Order_Item_Product|null $item       Order item if available.
	 * @return string Unit name.
	 */
	private function getItemUnit( ?int $product_id = null, ?WC_Order_Item_Product $item = null ): string {
		$unit = self::DEFAULT_UNIT;

		/**
		 * Filter the default unit for invoice items.
		 *
		 * @param string                     $unit       Default unit (e.g., 'szt.').
		 * @param int|null                   $product_id Product ID if available.
		 * @param WC_Order_Item_Product|null $item       Order item if available.
		 */
		return apply_filters( 'ihumbak_invoice_item_unit', $unit, $product_id, $item );
	}

	/**
	 * Get shipping unit with filter support.
	 *
	 * @param WC_Order $order WooCommerce order.
	 * @return string Unit name.
	 */
	private function getShippingUnit( WC_Order $order ): string {
		$unit = self::DEFAULT_UNIT;

		/**
		 * Filter the unit for shipping items on invoices.
		 *
		 * @param string   $unit  Default unit (e.g., 'szt.').
		 * @param WC_Order $order WooCommerce order.
		 */
		return apply_filters( 'ihumbak_invoice_shipping_unit', $unit, $order );
	}

	/**
	 * Get payment method map with filter support.
	 *
	 * @return array<string, string>
	 */
	private function getPaymentMethodMap(): array {
		/**
		 * Filter the payment method mapping from WooCommerce to invoice payment types.
		 *
		 * @param array<string, string> $map Default payment method map.
		 */
		return apply_filters( 'ihumbak_payment_method_map', self::DEFAULT_PAYMENT_METHOD_MAP );
	}

	/**
	 * Calculate effective tax rate from net amount and tax amount.
	 *
	 * @param float $net_amount Net amount.
	 * @param float $tax_amount Tax amount.
	 * @return float Tax rate percentage.
	 */
	private function calculateTaxRate( float $net_amount, float $tax_amount ): float {
		if ( $net_amount <= 0 ) {
			return 0.0;
		}

		$rate = ( $tax_amount / $net_amount ) * 100;

		// Round to nearest common Polish tax rate.
		$common_rates = array( 0, 5, 8, 23 );
		$closest      = 0;
		$min_diff     = PHP_FLOAT_MAX;

		foreach ( $common_rates as $common_rate ) {
			$diff = abs( $rate - $common_rate );
			if ( $diff < $min_diff ) {
				$min_diff = $diff;
				$closest  = $common_rate;
			}
		}

		// If difference is more than 1%, use calculated rate.
		if ( $min_diff > 1 ) {
			return round( $rate, 2 );
		}

		return (float) $closest;
	}
}
