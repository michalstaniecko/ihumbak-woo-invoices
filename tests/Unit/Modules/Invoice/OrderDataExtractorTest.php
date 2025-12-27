<?php
/**
 * OrderDataExtractor unit tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Modules\Invoice
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Modules\Invoice;

use IHumbak\Invoices\Modules\Invoice\OrderDataExtractor;
use IHumbak\Invoices\Modules\Invoice\CalculationService;
use PHPUnit\Framework\TestCase;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;

/**
 * Tests for OrderDataExtractor.
 */
class OrderDataExtractorTest extends TestCase {

	/**
	 * Service under test.
	 *
	 * @var OrderDataExtractor
	 */
	private OrderDataExtractor $extractor;

	/**
	 * Calculation service.
	 *
	 * @var CalculationService
	 */
	private CalculationService $calculation_service;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->calculation_service = new CalculationService();
		$this->extractor           = new OrderDataExtractor( $this->calculation_service );
	}

	/**
	 * Create a mock WC_Order.
	 *
	 * @param array<string, mixed> $data Order data.
	 * @return WC_Order
	 */
	private function createOrder( array $data = array() ): WC_Order {
		$order = new WC_Order();
		$order->set_data( $data );
		return $order;
	}

	/**
	 * Create a mock WC_Order_Item_Product.
	 *
	 * @param array<string, mixed> $data    Item data.
	 * @param WC_Product|null      $product Associated product.
	 * @return WC_Order_Item_Product
	 */
	private function createOrderItem( array $data = array(), ?WC_Product $product = null ): WC_Order_Item_Product {
		$item = new WC_Order_Item_Product();
		$item->set_data( $data );
		if ( null !== $product ) {
			$item->set_product( $product );
		}
		return $item;
	}

	/**
	 * Create a mock WC_Product.
	 *
	 * @param string $sku Product SKU.
	 * @return WC_Product
	 */
	private function createProduct( string $sku = '' ): WC_Product {
		return new WC_Product( $sku );
	}

	// =========================================================================
	// Tests for extractItems()
	// =========================================================================

	/**
	 * Test extracting items from order with products.
	 */
	public function test_extract_items_from_order_with_products(): void {
		$order = $this->createOrder();

		$product = $this->createProduct( 'SKU-001' );
		$item    = $this->createOrderItem(
			array(
				'product_id'   => 123,
				'name'         => 'Test Product',
				'quantity'     => 2,
				'subtotal'     => '200.00',
				'subtotal_tax' => '46.00',
			),
			$product
		);
		$order->add_item( $item );

		$result = $this->extractor->extractItems( $order );

		$this->assertCount( 1, $result );
		$this->assertEquals( 123, $result[0]['product_id'] );
		$this->assertEquals( 'Test Product', $result[0]['name'] );
		$this->assertEquals( 'SKU-001', $result[0]['sku'] );
		$this->assertEquals( 2.0, $result[0]['quantity'] );
	}

	/**
	 * Test extracting items returns empty array for order without products.
	 */
	public function test_extract_items_returns_empty_for_order_without_products(): void {
		$order = $this->createOrder();

		$result = $this->extractor->extractItems( $order );

		$this->assertEmpty( $result );
		$this->assertIsArray( $result );
	}

	/**
	 * Test extracting items calculates correct values.
	 */
	public function test_extract_items_calculates_correct_values(): void {
		$order = $this->createOrder();

		$product = $this->createProduct();
		$item    = $this->createOrderItem(
			array(
				'product_id'   => 1,
				'name'         => 'Product',
				'quantity'     => 1,
				'subtotal'     => '100.00',
				'subtotal_tax' => '23.00',
			),
			$product
		);
		$order->add_item( $item );

		$result = $this->extractor->extractItems( $order );

		$this->assertEquals( 100.00, $result[0]['unit_price_net'] );
		$this->assertEquals( 123.00, $result[0]['unit_price_gross'] );
		$this->assertEquals( 23.0, $result[0]['tax_rate'] );
		$this->assertEquals( 23.00, $result[0]['tax_amount'] );
		$this->assertEquals( 100.00, $result[0]['line_total_net'] );
		$this->assertEquals( 123.00, $result[0]['line_total_gross'] );
	}

	/**
	 * Test extracting items handles zero quantity by setting to 1.0.
	 */
	public function test_extract_items_handles_zero_quantity(): void {
		$order = $this->createOrder();

		$product = $this->createProduct();
		$item    = $this->createOrderItem(
			array(
				'product_id'   => 1,
				'name'         => 'Product',
				'quantity'     => 0,
				'subtotal'     => '100.00',
				'subtotal_tax' => '23.00',
			),
			$product
		);
		$order->add_item( $item );

		$result = $this->extractor->extractItems( $order );

		$this->assertEquals( 1.0, $result[0]['quantity'] );
	}

	/**
	 * Test extracting items includes SKU from product.
	 */
	public function test_extract_items_includes_sku_from_product(): void {
		$order = $this->createOrder();

		$product = $this->createProduct( 'PRODUCT-SKU-123' );
		$item    = $this->createOrderItem(
			array(
				'product_id'   => 1,
				'name'         => 'Product',
				'quantity'     => 1,
				'subtotal'     => '100.00',
				'subtotal_tax' => '23.00',
			),
			$product
		);
		$order->add_item( $item );

		$result = $this->extractor->extractItems( $order );

		$this->assertEquals( 'PRODUCT-SKU-123', $result[0]['sku'] );
	}

	/**
	 * Test extracting items handles missing product (empty SKU).
	 */
	public function test_extract_items_handles_missing_product(): void {
		$order = $this->createOrder();

		// Item without product.
		$item = $this->createOrderItem(
			array(
				'product_id'   => 1,
				'name'         => 'Product',
				'quantity'     => 1,
				'subtotal'     => '100.00',
				'subtotal_tax' => '23.00',
			)
		);
		$order->add_item( $item );

		$result = $this->extractor->extractItems( $order );

		$this->assertEquals( '', $result[0]['sku'] );
	}

	// =========================================================================
	// Tests for extractShipping()
	// =========================================================================

	/**
	 * Test extracting shipping returns item when shipping exists.
	 */
	public function test_extract_shipping_returns_item_when_shipping_exists(): void {
		$order = $this->createOrder(
			array(
				'shipping_total'  => '20.00',
				'shipping_tax'    => '4.60',
				'shipping_method' => 'Flat Rate',
			)
		);

		$result = $this->extractor->extractShipping( $order );

		$this->assertNotNull( $result );
		$this->assertNull( $result['product_id'] );
		$this->assertEquals( 'Flat Rate', $result['name'] );
		$this->assertEquals( '', $result['sku'] );
		$this->assertEquals( 1.0, $result['quantity'] );
		$this->assertEquals( 20.00, $result['unit_price_net'] );
		$this->assertEquals( 23.0, $result['tax_rate'] );
	}

	/**
	 * Test extracting shipping returns null for free shipping.
	 */
	public function test_extract_shipping_returns_null_for_free_shipping(): void {
		$order = $this->createOrder(
			array(
				'shipping_total'  => '0.00',
				'shipping_tax'    => '0.00',
				'shipping_method' => 'Free Shipping',
			)
		);

		$result = $this->extractor->extractShipping( $order );

		$this->assertNull( $result );
	}

	/**
	 * Test extracting shipping returns null when no shipping configured.
	 */
	public function test_extract_shipping_returns_null_for_zero_shipping(): void {
		$order = $this->createOrder();

		$result = $this->extractor->extractShipping( $order );

		$this->assertNull( $result );
	}

	/**
	 * Test extracting shipping uses default name when method is empty.
	 */
	public function test_extract_shipping_uses_default_name_when_method_empty(): void {
		$order = $this->createOrder(
			array(
				'shipping_total' => '15.00',
				'shipping_tax'   => '3.45',
			)
		);

		$result = $this->extractor->extractShipping( $order );

		$this->assertNotNull( $result );
		$this->assertEquals( 'Shipping', $result['name'] );
	}

	// =========================================================================
	// Tests for extractBuyer()
	// =========================================================================

	/**
	 * Test extracting buyer uses company name when available.
	 */
	public function test_extract_buyer_uses_company_name_when_available(): void {
		$order = $this->createOrder(
			array(
				'billing_company'    => 'ACME Corporation',
				'billing_first_name' => 'John',
				'billing_last_name'  => 'Doe',
			)
		);

		$result = $this->extractor->extractBuyer( $order );

		$this->assertEquals( 'ACME Corporation', $result['name'] );
	}

	/**
	 * Test extracting buyer uses personal name when no company.
	 */
	public function test_extract_buyer_uses_personal_name_when_no_company(): void {
		$order = $this->createOrder(
			array(
				'billing_first_name' => 'John',
				'billing_last_name'  => 'Doe',
			)
		);

		$result = $this->extractor->extractBuyer( $order );

		$this->assertEquals( 'John Doe', $result['name'] );
	}

	/**
	 * Test extracting buyer combines address lines.
	 */
	public function test_extract_buyer_combines_address_lines(): void {
		$order = $this->createOrder(
			array(
				'billing_address_1' => '123 Main Street',
				'billing_address_2' => 'Apt 4B',
			)
		);

		$result = $this->extractor->extractBuyer( $order );

		$this->assertEquals( '123 Main Street Apt 4B', $result['address'] );
	}

	/**
	 * Test extracting buyer gets NIP from meta.
	 */
	public function test_extract_buyer_gets_nip_from_meta(): void {
		$order = $this->createOrder();
		$order->set_meta( '_billing_nip', '1234567890' );

		$result = $this->extractor->extractBuyer( $order );

		$this->assertEquals( '1234567890', $result['nip'] );
	}

	/**
	 * Test extracting buyer handles missing NIP.
	 */
	public function test_extract_buyer_handles_missing_nip(): void {
		$order = $this->createOrder();

		$result = $this->extractor->extractBuyer( $order );

		$this->assertEquals( '', $result['nip'] );
	}

	/**
	 * Test extracting buyer defaults country to PL.
	 */
	public function test_extract_buyer_defaults_country_to_pl(): void {
		$order = $this->createOrder();

		$result = $this->extractor->extractBuyer( $order );

		$this->assertEquals( 'PL', $result['country'] );
	}

	/**
	 * Test extracting buyer includes all fields.
	 */
	public function test_extract_buyer_includes_all_fields(): void {
		$order = $this->createOrder(
			array(
				'billing_company'    => 'Test Company',
				'billing_address_1'  => 'Test Street 1',
				'billing_postcode'   => '00-001',
				'billing_city'       => 'Warsaw',
				'billing_country'    => 'DE',
				'billing_email'      => 'test@example.com',
				'billing_phone'      => '+48123456789',
			)
		);
		$order->set_meta( '_billing_nip', '9999999999' );

		$result = $this->extractor->extractBuyer( $order );

		$this->assertEquals( 'Test Company', $result['name'] );
		$this->assertEquals( 'Test Street 1', $result['address'] );
		$this->assertEquals( '00-001', $result['postcode'] );
		$this->assertEquals( 'Warsaw', $result['city'] );
		$this->assertEquals( 'DE', $result['country'] );
		$this->assertEquals( '9999999999', $result['nip'] );
		$this->assertEquals( 'test@example.com', $result['email'] );
		$this->assertEquals( '+48123456789', $result['phone'] );
	}

	// =========================================================================
	// Tests for extractPaymentMethod()
	// =========================================================================

	/**
	 * Data provider for payment method mapping tests.
	 *
	 * @return array<string, array{input: string, expected_type: string}>
	 */
	public static function paymentMethodMappingProvider(): array {
		return array(
			'bacs to transfer'       => array(
				'input'         => 'bacs',
				'expected_type' => 'transfer',
			),
			'cod to cash'            => array(
				'input'         => 'cod',
				'expected_type' => 'cash',
			),
			'cheque to transfer'     => array(
				'input'         => 'cheque',
				'expected_type' => 'transfer',
			),
			'stripe to card'         => array(
				'input'         => 'stripe',
				'expected_type' => 'card',
			),
			'stripe_cc to card'      => array(
				'input'         => 'stripe_cc',
				'expected_type' => 'card',
			),
			'paypal to online'       => array(
				'input'         => 'paypal',
				'expected_type' => 'online',
			),
			'przelewy24 to online'   => array(
				'input'         => 'przelewy24',
				'expected_type' => 'online',
			),
			'tpay to online'         => array(
				'input'         => 'tpay',
				'expected_type' => 'online',
			),
			'payu to online'         => array(
				'input'         => 'payu',
				'expected_type' => 'online',
			),
			'dotpay to online'       => array(
				'input'         => 'dotpay',
				'expected_type' => 'online',
			),
			'partial match stripe_sepa to card' => array(
				'input'         => 'stripe_sepa',
				'expected_type' => 'card',
			),
			'unknown defaults to online' => array(
				'input'         => 'unknown_gateway',
				'expected_type' => 'online',
			),
		);
	}

	/**
	 * Test payment method mapping with various input values.
	 *
	 * @dataProvider paymentMethodMappingProvider
	 *
	 * @param string $input         WooCommerce payment method.
	 * @param string $expected_type Expected mapped type value.
	 */
	public function test_extract_payment_method_mapping( string $input, string $expected_type ): void {
		$order = $this->createOrder( array( 'payment_method' => $input ) );

		$result = $this->extractor->extractPaymentMethod( $order );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'type', $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertArrayHasKey( 'title', $result );
		$this->assertEquals( $expected_type, $result['type'] );
		$this->assertEquals( $input, $result['id'] );
	}

	/**
	 * Test extracting payment method returns empty values for no method.
	 */
	public function test_extract_payment_method_returns_empty_for_no_method(): void {
		$order = $this->createOrder();

		$result = $this->extractor->extractPaymentMethod( $order );

		$this->assertIsArray( $result );
		$this->assertEquals( '', $result['type'] );
		$this->assertEquals( '', $result['id'] );
		$this->assertEquals( '', $result['title'] );
	}

	/**
	 * Test extractPaymentMethod returns title from WC order.
	 */
	public function test_extract_payment_method_returns_title(): void {
		$order = $this->createOrder(
			array(
				'payment_method'       => 'przelewy24',
				'payment_method_title' => 'Przelewy24',
			)
		);

		$result = $this->extractor->extractPaymentMethod( $order );

		$this->assertEquals( 'online', $result['type'] );
		$this->assertEquals( 'przelewy24', $result['id'] );
		$this->assertEquals( 'Przelewy24', $result['title'] );
	}

	// =========================================================================
	// Tests for extractAll()
	// =========================================================================

	/**
	 * Test extractAll returns complete data structure.
	 */
	public function test_extract_all_returns_complete_data(): void {
		$order = $this->createOrder(
			array(
				'currency'             => 'PLN',
				'payment_method'       => 'bacs',
				'payment_method_title' => 'Bank Transfer',
				'billing_first_name'   => 'John',
				'billing_last_name'    => 'Doe',
			)
		);

		$product = $this->createProduct( 'SKU-001' );
		$item    = $this->createOrderItem(
			array(
				'product_id'   => 1,
				'name'         => 'Product',
				'quantity'     => 1,
				'subtotal'     => '100.00',
				'subtotal_tax' => '23.00',
			),
			$product
		);
		$order->add_item( $item );

		$result = $this->extractor->extractAll( $order );

		$this->assertArrayHasKey( 'items', $result );
		$this->assertArrayHasKey( 'buyer', $result );
		$this->assertArrayHasKey( 'payment_method', $result );
		$this->assertArrayHasKey( 'currency', $result );

		$this->assertCount( 1, $result['items'] );
		$this->assertEquals( 'John Doe', $result['buyer']['name'] );

		// payment_method is now an array with type, id, title.
		$this->assertIsArray( $result['payment_method'] );
		$this->assertEquals( 'transfer', $result['payment_method']['type'] );
		$this->assertEquals( 'bacs', $result['payment_method']['id'] );
		$this->assertEquals( 'Bank Transfer', $result['payment_method']['title'] );

		$this->assertEquals( 'PLN', $result['currency'] );
	}

	/**
	 * Test extractAll includes shipping when present.
	 */
	public function test_extract_all_includes_shipping_when_present(): void {
		$order = $this->createOrder(
			array(
				'shipping_total'  => '20.00',
				'shipping_tax'    => '4.60',
				'shipping_method' => 'Flat Rate',
			)
		);

		$product = $this->createProduct();
		$item    = $this->createOrderItem(
			array(
				'product_id'   => 1,
				'name'         => 'Product',
				'quantity'     => 1,
				'subtotal'     => '100.00',
				'subtotal_tax' => '23.00',
			),
			$product
		);
		$order->add_item( $item );

		$result = $this->extractor->extractAll( $order );

		$this->assertCount( 2, $result['items'] );
		$this->assertEquals( 'Flat Rate', $result['items'][1]['name'] );
	}

	/**
	 * Test extractAll excludes shipping when free.
	 */
	public function test_extract_all_excludes_shipping_when_free(): void {
		$order = $this->createOrder(
			array(
				'shipping_total' => '0.00',
				'shipping_tax'   => '0.00',
			)
		);

		$product = $this->createProduct();
		$item    = $this->createOrderItem(
			array(
				'product_id'   => 1,
				'name'         => 'Product',
				'quantity'     => 1,
				'subtotal'     => '100.00',
				'subtotal_tax' => '23.00',
			),
			$product
		);
		$order->add_item( $item );

		$result = $this->extractor->extractAll( $order );

		$this->assertCount( 1, $result['items'] );
	}

	// =========================================================================
	// Tests for calculateTaxRate (tested through extractItems/extractShipping)
	// =========================================================================

	/**
	 * Test tax rate calculation rounds to 23 percent.
	 */
	public function test_tax_rate_rounds_to_23_percent(): void {
		$order = $this->createOrder();

		$product = $this->createProduct();
		$item    = $this->createOrderItem(
			array(
				'product_id'   => 1,
				'name'         => 'Product',
				'quantity'     => 1,
				'subtotal'     => '100.00',
				'subtotal_tax' => '22.50', // 22.5% is within 1% of 23%.
			),
			$product
		);
		$order->add_item( $item );

		$result = $this->extractor->extractItems( $order );

		$this->assertEquals( 23.0, $result[0]['tax_rate'] );
	}

	/**
	 * Test tax rate calculation rounds to 8 percent.
	 */
	public function test_tax_rate_rounds_to_8_percent(): void {
		$order = $this->createOrder();

		$product = $this->createProduct();
		$item    = $this->createOrderItem(
			array(
				'product_id'   => 1,
				'name'         => 'Product',
				'quantity'     => 1,
				'subtotal'     => '100.00',
				'subtotal_tax' => '7.80', // 7.8% is within 1% of 8%.
			),
			$product
		);
		$order->add_item( $item );

		$result = $this->extractor->extractItems( $order );

		$this->assertEquals( 8.0, $result[0]['tax_rate'] );
	}

	/**
	 * Test tax rate calculation rounds to 5 percent.
	 */
	public function test_tax_rate_rounds_to_5_percent(): void {
		$order = $this->createOrder();

		$product = $this->createProduct();
		$item    = $this->createOrderItem(
			array(
				'product_id'   => 1,
				'name'         => 'Product',
				'quantity'     => 1,
				'subtotal'     => '100.00',
				'subtotal_tax' => '5.00',
			),
			$product
		);
		$order->add_item( $item );

		$result = $this->extractor->extractItems( $order );

		$this->assertEquals( 5.0, $result[0]['tax_rate'] );
	}

	/**
	 * Test tax rate calculation rounds to 0 percent.
	 */
	public function test_tax_rate_rounds_to_0_percent(): void {
		$order = $this->createOrder();

		$product = $this->createProduct();
		$item    = $this->createOrderItem(
			array(
				'product_id'   => 1,
				'name'         => 'Product',
				'quantity'     => 1,
				'subtotal'     => '100.00',
				'subtotal_tax' => '0.00',
			),
			$product
		);
		$order->add_item( $item );

		$result = $this->extractor->extractItems( $order );

		$this->assertEquals( 0.0, $result[0]['tax_rate'] );
	}

	/**
	 * Test tax rate returns exact value when difference is over 1 percent.
	 */
	public function test_tax_rate_returns_exact_when_diff_over_1_percent(): void {
		$order = $this->createOrder();

		$product = $this->createProduct();
		$item    = $this->createOrderItem(
			array(
				'product_id'   => 1,
				'name'         => 'Product',
				'quantity'     => 1,
				'subtotal'     => '100.00',
				'subtotal_tax' => '15.00', // 15% is not close to any common rate.
			),
			$product
		);
		$order->add_item( $item );

		$result = $this->extractor->extractItems( $order );

		$this->assertEquals( 15.0, $result[0]['tax_rate'] );
	}

	/**
	 * Test tax rate returns 0 for zero net amount.
	 */
	public function test_tax_rate_returns_0_for_zero_net_amount(): void {
		$order = $this->createOrder();

		$product = $this->createProduct();
		$item    = $this->createOrderItem(
			array(
				'product_id'   => 1,
				'name'         => 'Free Product',
				'quantity'     => 1,
				'subtotal'     => '0.00',
				'subtotal_tax' => '0.00',
			),
			$product
		);
		$order->add_item( $item );

		$result = $this->extractor->extractItems( $order );

		$this->assertEquals( 0.0, $result[0]['tax_rate'] );
	}

	// =========================================================================
	// Additional edge case tests
	// =========================================================================

	/**
	 * Test extracting multiple items from order.
	 */
	public function test_extract_multiple_items_from_order(): void {
		$order = $this->createOrder();

		$product1 = $this->createProduct( 'SKU-001' );
		$item1    = $this->createOrderItem(
			array(
				'product_id'   => 1,
				'name'         => 'Product 1',
				'quantity'     => 2,
				'subtotal'     => '200.00',
				'subtotal_tax' => '46.00',
			),
			$product1
		);

		$product2 = $this->createProduct( 'SKU-002' );
		$item2    = $this->createOrderItem(
			array(
				'product_id'   => 2,
				'name'         => 'Product 2',
				'quantity'     => 1,
				'subtotal'     => '50.00',
				'subtotal_tax' => '4.00',
			),
			$product2
		);

		$order->add_item( $item1 );
		$order->add_item( $item2 );

		$result = $this->extractor->extractItems( $order );

		$this->assertCount( 2, $result );
		$this->assertEquals( 'SKU-001', $result[0]['sku'] );
		$this->assertEquals( 'SKU-002', $result[1]['sku'] );
	}

	/**
	 * Test extracting buyer with custom NIP meta key.
	 */
	public function test_extract_buyer_with_custom_nip_meta_key(): void {
		$order = $this->createOrder();
		$order->set_meta( 'custom_nip_field', '0987654321' );

		$result = $this->extractor->extractBuyer( $order, 'custom_nip_field' );

		$this->assertEquals( '0987654321', $result['nip'] );
	}

	/**
	 * Test extractAll with custom NIP meta key.
	 */
	public function test_extract_all_with_custom_nip_meta_key(): void {
		$order = $this->createOrder();
		$order->set_meta( 'my_nip', '1111111111' );

		$result = $this->extractor->extractAll( $order, 'my_nip' );

		$this->assertEquals( '1111111111', $result['buyer']['nip'] );
	}

	/**
	 * Test constructor creates default CalculationService.
	 */
	public function test_constructor_creates_default_calculation_service(): void {
		$extractor = new OrderDataExtractor();

		$order = $this->createOrder();

		$product = $this->createProduct();
		$item    = $this->createOrderItem(
			array(
				'product_id'   => 1,
				'name'         => 'Product',
				'quantity'     => 1,
				'subtotal'     => '100.00',
				'subtotal_tax' => '23.00',
			),
			$product
		);
		$order->add_item( $item );

		$result = $extractor->extractItems( $order );

		// If CalculationService works, values should be calculated.
		$this->assertEquals( 123.00, $result[0]['line_total_gross'] );
	}

	/**
	 * Test extracting item with negative quantity normalizes to 1.
	 */
	public function test_extract_items_handles_negative_quantity(): void {
		$order = $this->createOrder();

		$product = $this->createProduct();
		$item    = $this->createOrderItem(
			array(
				'product_id'   => 1,
				'name'         => 'Product',
				'quantity'     => -5,
				'subtotal'     => '100.00',
				'subtotal_tax' => '23.00',
			),
			$product
		);
		$order->add_item( $item );

		$result = $this->extractor->extractItems( $order );

		$this->assertEquals( 1.0, $result[0]['quantity'] );
	}

	/**
	 * Test default unit is used for items.
	 */
	public function test_extract_items_uses_default_unit(): void {
		$order = $this->createOrder();

		$product = $this->createProduct();
		$item    = $this->createOrderItem(
			array(
				'product_id'   => 1,
				'name'         => 'Product',
				'quantity'     => 1,
				'subtotal'     => '100.00',
				'subtotal_tax' => '23.00',
			),
			$product
		);
		$order->add_item( $item );

		$result = $this->extractor->extractItems( $order );

		$this->assertEquals( 'szt.', $result[0]['unit'] );
	}

	/**
	 * Test default unit is used for shipping.
	 */
	public function test_extract_shipping_uses_default_unit(): void {
		$order = $this->createOrder(
			array(
				'shipping_total'  => '20.00',
				'shipping_tax'    => '4.60',
				'shipping_method' => 'Flat Rate',
			)
		);

		$result = $this->extractor->extractShipping( $order );

		$this->assertEquals( 'szt.', $result['unit'] );
	}
}
