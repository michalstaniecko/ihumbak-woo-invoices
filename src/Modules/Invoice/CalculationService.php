<?php
/**
 * Calculation Service.
 *
 * @package IHumbak\Invoices\Modules\Invoice
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Invoice;

use IHumbak\Invoices\Models\DocumentItem;

/**
 * Service for calculating document values.
 * All calculations are done server-side (PHP).
 */
class CalculationService {

	/**
	 * Default tax rates available in Poland.
	 *
	 * @var array<int|string, string>
	 */
	public const TAX_RATES = array(
		23   => '23%',
		8    => '8%',
		5    => '5%',
		0    => '0%',
		'zw' => 'zw.',  // Exempt.
		'np' => 'np.',  // Not applicable.
	);

	/**
	 * Calculate item values from net price.
	 *
	 * @param float $unit_price_net Net unit price.
	 * @param float $quantity       Quantity.
	 * @param float $tax_rate       Tax rate percentage.
	 * @return array<string, float>
	 */
	public function calculateFromNet( float $unit_price_net, float $quantity, float $tax_rate ): array {
		$line_total_net   = $this->round( $unit_price_net * $quantity );
		$tax_amount       = $this->round( $line_total_net * ( $tax_rate / 100 ) );
		$line_total_gross = $this->round( $line_total_net + $tax_amount );
		$unit_price_gross = $this->round( $unit_price_net * ( 1 + $tax_rate / 100 ) );

		return array(
			'unit_price_net'   => $unit_price_net,
			'unit_price_gross' => $unit_price_gross,
			'tax_rate'         => $tax_rate,
			'tax_amount'       => $tax_amount,
			'line_total_net'   => $line_total_net,
			'line_total_gross' => $line_total_gross,
		);
	}

	/**
	 * Calculate item values from gross price.
	 *
	 * @param float $unit_price_gross Gross unit price.
	 * @param float $quantity         Quantity.
	 * @param float $tax_rate         Tax rate percentage.
	 * @return array<string, float>
	 */
	public function calculateFromGross( float $unit_price_gross, float $quantity, float $tax_rate ): array {
		$unit_price_net   = $this->round( $unit_price_gross / ( 1 + $tax_rate / 100 ) );
		$line_total_gross = $this->round( $unit_price_gross * $quantity );
		$line_total_net   = $this->round( $line_total_gross / ( 1 + $tax_rate / 100 ) );
		$tax_amount       = $this->round( $line_total_gross - $line_total_net );

		return array(
			'unit_price_net'   => $unit_price_net,
			'unit_price_gross' => $unit_price_gross,
			'tax_rate'         => $tax_rate,
			'tax_amount'       => $tax_amount,
			'line_total_net'   => $line_total_net,
			'line_total_gross' => $line_total_gross,
		);
	}

	/**
	 * Calculate document totals from items.
	 *
	 * @param DocumentItem[] $items Document items.
	 * @return array<string, float>
	 */
	public function calculateTotals( array $items ): array {
		$subtotal  = 0.0;
		$tax_total = 0.0;
		$total     = 0.0;

		foreach ( $items as $item ) {
			$subtotal  += $item->getLineTotalNet();
			$tax_total += $item->getTaxAmount();
			$total     += $item->getLineTotalGross();
		}

		return array(
			'subtotal'  => $this->round( $subtotal ),
			'tax_total' => $this->round( $tax_total ),
			'total'     => $this->round( $total ),
		);
	}

	/**
	 * Calculate document totals from raw item data.
	 *
	 * @param array<int, array<string, mixed>> $items_data Raw items data.
	 * @return array<string, mixed>
	 */
	public function calculateFromItemsData( array $items_data ): array {
		$calculated_items = array();
		$subtotal         = 0.0;
		$tax_total        = 0.0;
		$total            = 0.0;

		foreach ( $items_data as $index => $item_data ) {
			$quantity   = (float) ( $item_data['quantity'] ?? 1 );
			$tax_rate   = (float) ( $item_data['tax_rate'] ?? 23 );
			$price_type = $item_data['price_type'] ?? 'net';

			if ( 'gross' === $price_type ) {
				$unit_price = (float) ( $item_data['unit_price_gross'] ?? 0 );
				$calculated = $this->calculateFromGross( $unit_price, $quantity, $tax_rate );
			} else {
				$unit_price = (float) ( $item_data['unit_price_net'] ?? 0 );
				$calculated = $this->calculateFromNet( $unit_price, $quantity, $tax_rate );
			}

			$calculated_items[ $index ] = array_merge(
				array(
					'name' => $item_data['name'] ?? '',
					'unit' => $item_data['unit'] ?? 'szt.',
				),
				$calculated
			);

			$subtotal  += $calculated['line_total_net'];
			$tax_total += $calculated['tax_amount'];
			$total     += $calculated['line_total_gross'];
		}

		return array(
			'items'     => $calculated_items,
			'subtotal'  => $this->round( $subtotal ),
			'tax_total' => $this->round( $tax_total ),
			'total'     => $this->round( $total ),
		);
	}

	/**
	 * Calculate VAT breakdown by rate.
	 *
	 * @param DocumentItem[] $items Document items.
	 * @return array<string, array<string, float>>
	 */
	public function calculateVatBreakdown( array $items ): array {
		$breakdown = array();

		foreach ( $items as $item ) {
			$rate_key = (string) $item->getTaxRate();

			if ( ! isset( $breakdown[ $rate_key ] ) ) {
				$breakdown[ $rate_key ] = array(
					'rate'        => $item->getTaxRate(),
					'net_total'   => 0.0,
					'tax_amount'  => 0.0,
					'gross_total' => 0.0,
				);
			}

			$breakdown[ $rate_key ]['net_total']   += $item->getLineTotalNet();
			$breakdown[ $rate_key ]['tax_amount']  += $item->getTaxAmount();
			$breakdown[ $rate_key ]['gross_total'] += $item->getLineTotalGross();
		}

		// Round all values.
		foreach ( $breakdown as $rate => $values ) {
			$breakdown[ $rate ]['net_total']   = $this->round( $values['net_total'] );
			$breakdown[ $rate ]['tax_amount']  = $this->round( $values['tax_amount'] );
			$breakdown[ $rate ]['gross_total'] = $this->round( $values['gross_total'] );
		}

		// Sort by tax rate descending.
		uksort( $breakdown, fn( $a, $b ) => (float) $b <=> (float) $a );

		return $breakdown;
	}

	/**
	 * Round value to 2 decimal places.
	 *
	 * @param float $value Value to round.
	 * @return float
	 */
	private function round( float $value ): float {
		return round( $value, 2 );
	}

	/**
	 * Get available tax rates.
	 *
	 * @return array<int|string, string>
	 */
	public static function getTaxRates(): array {
		return self::TAX_RATES;
	}

	/**
	 * Format money value.
	 *
	 * @param float  $amount   Amount.
	 * @param string $currency Currency code.
	 * @return string
	 */
	public static function formatMoney( float $amount, string $currency = 'PLN' ): string {
		return number_format( $amount, 2, ',', ' ' ) . ' ' . $currency;
	}
}
