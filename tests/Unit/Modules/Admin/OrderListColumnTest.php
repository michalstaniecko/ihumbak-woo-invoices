<?php
/**
 * OrderListColumn unit tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Modules\Admin
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Modules\Admin;

use IHumbak\Invoices\Modules\Admin\OrderListColumn;
use IHumbak\Invoices\Infrastructure\Database\DocumentRepository;
use PHPUnit\Framework\TestCase;

/**
 * Test case for OrderListColumn class.
 */
class OrderListColumnTest extends TestCase {

	/**
	 * OrderListColumn instance.
	 *
	 * @var OrderListColumn
	 */
	private OrderListColumn $column;

	/**
	 * Mock repository.
	 *
	 * @var DocumentRepository|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $repository;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->repository = $this->createMock( DocumentRepository::class );
		$this->column     = new OrderListColumn( $this->repository );
	}

	/**
	 * Test add_column inserts after order_status.
	 *
	 * @return void
	 */
	public function test_add_column_inserts_after_status(): void {
		$columns = array(
			'cb'           => '<input type="checkbox" />',
			'order_number' => 'Order',
			'order_status' => 'Status',
			'order_total'  => 'Total',
		);

		$result = $this->column->add_column( $columns );

		$keys         = array_keys( $result );
		$status_index = array_search( 'order_status', $keys, true );
		$docs_index   = array_search( 'ihumbak_documents', $keys, true );

		$this->assertNotFalse( $status_index );
		$this->assertNotFalse( $docs_index );
		$this->assertEquals( $status_index + 1, $docs_index );
	}

	/**
	 * Test add_column adds column even without order_status.
	 *
	 * @return void
	 */
	public function test_add_column_fallback_when_no_status_column(): void {
		$columns = array(
			'cb'           => '<input type="checkbox" />',
			'order_number' => 'Order',
			'order_total'  => 'Total',
		);

		$result = $this->column->add_column( $columns );

		$this->assertArrayHasKey( 'ihumbak_documents', $result );
	}

	/**
	 * Test add_column sets correct label.
	 *
	 * @return void
	 */
	public function test_add_column_sets_correct_label(): void {
		$columns = array(
			'order_status' => 'Status',
		);

		$result = $this->column->add_column( $columns );

		$this->assertEquals( 'Documents', $result['ihumbak_documents'] );
	}

	/**
	 * Test is_enabled returns true by default.
	 *
	 * @return void
	 */
	public function test_is_enabled_returns_true_by_default(): void {
		// When no option is set, should return true.
		$this->assertTrue( $this->column->is_enabled() );
	}

	/**
	 * Test is_enabled returns true when setting is enabled.
	 *
	 * @return void
	 */
	public function test_is_enabled_returns_true_when_setting_enabled(): void {
		update_option(
			'ihumbak_invoices_settings',
			array(
				'display' => array( 'show_order_column' => true ),
			)
		);

		$this->assertTrue( $this->column->is_enabled() );
	}

	/**
	 * Test is_enabled returns false when setting is disabled.
	 *
	 * @return void
	 */
	public function test_is_enabled_returns_false_when_setting_disabled(): void {
		update_option(
			'ihumbak_invoices_settings',
			array(
				'display' => array( 'show_order_column' => false ),
			)
		);

		$this->assertFalse( $this->column->is_enabled() );
	}

	/**
	 * Test shorten_number with short number.
	 *
	 * @return void
	 */
	public function test_shorten_number_keeps_short_numbers(): void {
		$reflection = new \ReflectionClass( $this->column );
		$method     = $reflection->getMethod( 'shorten_number' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->column, 'FV/2025/0001' );

		$this->assertEquals( 'FV/2025/0001', $result );
	}

	/**
	 * Test shorten_number with long pattern number.
	 *
	 * @return void
	 */
	public function test_shorten_number_shortens_long_pattern(): void {
		$reflection = new \ReflectionClass( $this->column );
		$method     = $reflection->getMethod( 'shorten_number' );
		$method->setAccessible( true );

		// Use a number that is longer than 15 characters.
		$result = $method->invoke( $this->column, 'FV/2025/01/00001' );

		$this->assertEquals( 'FV/.../00001', $result );
	}

	/**
	 * Test get_document_icon returns correct icons.
	 *
	 * @return void
	 */
	public function test_get_document_icon_returns_correct_icons(): void {
		$reflection = new \ReflectionClass( $this->column );
		$method     = $reflection->getMethod( 'get_document_icon' );
		$method->setAccessible( true );

		$invoice_icon     = $method->invoke( $this->column, 'invoice' );
		$receipt_icon     = $method->invoke( $this->column, 'receipt' );
		$credit_note_icon = $method->invoke( $this->column, 'credit_note' );
		$unknown_icon     = $method->invoke( $this->column, 'unknown' );

		$this->assertStringContainsString( 'dashicons-media-document', $invoice_icon );
		$this->assertStringContainsString( 'ihumbak-doc-icon', $invoice_icon );
		$this->assertStringContainsString( 'dashicons-media-text', $receipt_icon );
		$this->assertStringContainsString( 'dashicons-undo', $credit_note_icon );
		$this->assertEquals( '', $unknown_icon );
	}

	/**
	 * Test get_document_type_label returns correct labels.
	 *
	 * @return void
	 */
	public function test_get_document_type_label_returns_correct_labels(): void {
		$reflection = new \ReflectionClass( $this->column );
		$method     = $reflection->getMethod( 'get_document_type_label' );
		$method->setAccessible( true );

		$this->assertEquals( 'Invoice', $method->invoke( $this->column, 'invoice' ) );
		$this->assertEquals( 'Receipt', $method->invoke( $this->column, 'receipt' ) );
		$this->assertEquals( 'Credit Note', $method->invoke( $this->column, 'credit_note' ) );
		$this->assertEquals( 'unknown', $method->invoke( $this->column, 'unknown' ) );
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		delete_option( 'ihumbak_invoices_settings' );
		parent::tearDown();
	}
}
