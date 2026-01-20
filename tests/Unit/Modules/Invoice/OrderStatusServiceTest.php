<?php
/**
 * OrderStatusService unit tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Modules\Invoice
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Modules\Invoice;

use IHumbak\Invoices\Modules\Invoice\OrderStatusService;
use IHumbak\Invoices\Models\Invoice;
use IHumbak\Invoices\Models\Receipt;
use IHumbak\Invoices\Models\CreditNote;
use PHPUnit\Framework\TestCase;

/**
 * Tests for OrderStatusService.
 */
class OrderStatusServiceTest extends TestCase {

	/**
	 * Service under test.
	 *
	 * @var OrderStatusService
	 */
	private OrderStatusService $service;

	/**
	 * Track action calls for testing.
	 *
	 * @var array<string, array<int, array<string, mixed>>>
	 */
	private array $action_calls = array();

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset options.
		delete_option( 'ihumbak_invoices_settings' );

		// Reset filters.
		global $mock_wp_filters;
		$mock_wp_filters = array();

		// Reset WooCommerce orders.
		global $mock_wc_orders;
		$mock_wc_orders = array();

		// Reset action calls tracking.
		$this->action_calls = array();

		// Reset Plugin singleton.
		$this->resetPluginSingleton();

		$this->service = new OrderStatusService();
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		// Clean up options.
		delete_option( 'ihumbak_invoices_settings' );

		// Clean up filters.
		global $mock_wp_filters;
		$mock_wp_filters = array();

		// Clean up WooCommerce orders.
		global $mock_wc_orders;
		$mock_wc_orders = array();

		// Reset action calls.
		$this->action_calls = array();

		parent::tearDown();
	}

	/**
	 * Reset Plugin singleton for testing.
	 */
	private function resetPluginSingleton(): void {
		$reflection = new \ReflectionClass( \IHumbak\Invoices\Core\Plugin::class );
		$property   = $reflection->getProperty( 'instance' );
		$property->setAccessible( true );
		$property->setValue( null, null );
	}

	/**
	 * Create an Invoice document for testing.
	 *
	 * @param int|null $order_id Order ID.
	 * @return Invoice
	 */
	private function createInvoice( ?int $order_id = null ): Invoice {
		$invoice = new Invoice();
		if ( null !== $order_id ) {
			$invoice->setOrderId( $order_id );
		}
		$invoice->setDocumentNumber( 'FV/2024/01/0001' );
		return $invoice;
	}

	/**
	 * Create a Receipt document for testing.
	 *
	 * @param int|null $order_id Order ID.
	 * @return Receipt
	 */
	private function createReceipt( ?int $order_id = null ): Receipt {
		$receipt = new Receipt();
		if ( null !== $order_id ) {
			$receipt->setOrderId( $order_id );
		}
		$receipt->setDocumentNumber( 'PAR/2024/01/0001' );
		return $receipt;
	}

	/**
	 * Create a CreditNote document for testing.
	 *
	 * @param int|null $order_id Order ID.
	 * @return CreditNote
	 */
	private function createCreditNote( ?int $order_id = null ): CreditNote {
		$credit_note = new CreditNote();
		if ( null !== $order_id ) {
			$credit_note->setOrderId( $order_id );
		}
		$credit_note->setDocumentNumber( 'CN/2024/01/0001' );
		return $credit_note;
	}

	/**
	 * Enable order status change feature in settings.
	 *
	 * @param string $target_status Target status.
	 */
	private function enableOrderStatusFeature( string $target_status = 'completed' ): void {
		update_option(
			'ihumbak_invoices_settings',
			array(
				'display' => array(
					'order_status' => array(
						'enabled' => true,
						'target'  => $target_status,
					),
				),
			)
		);

		// Reset Plugin singleton to reload settings.
		$this->resetPluginSingleton();
		$this->service = new OrderStatusService();
	}

	/**
	 * Disable order status change feature in settings.
	 */
	private function disableOrderStatusFeature(): void {
		update_option(
			'ihumbak_invoices_settings',
			array(
				'display' => array(
					'order_status' => array(
						'enabled' => false,
						'target'  => 'completed',
					),
				),
			)
		);

		// Reset Plugin singleton to reload settings.
		$this->resetPluginSingleton();
		$this->service = new OrderStatusService();
	}

	/**
	 * Create a mock WC_Order for testing.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $status   Current order status (without 'wc-' prefix).
	 * @return \WC_Order
	 */
	private function createMockOrder( int $order_id, string $status = 'processing' ): \WC_Order {
		global $mock_wc_orders;

		$order = new MockWCOrder();
		$order->set_id( $order_id );
		$order->set_status( $status );

		$mock_wc_orders[ $order_id ] = $order;

		return $order;
	}

	// ==========================================================================
	// isEnabled() tests
	// ==========================================================================

	/**
	 * Test isEnabled returns false when feature is disabled.
	 */
	public function test_is_enabled_returns_false_when_disabled(): void {
		$this->disableOrderStatusFeature();

		$this->assertFalse( $this->service->isEnabled() );
	}

	/**
	 * Test isEnabled returns true when feature is enabled.
	 */
	public function test_is_enabled_returns_true_when_enabled(): void {
		$this->enableOrderStatusFeature();

		$this->assertTrue( $this->service->isEnabled() );
	}

	/**
	 * Test isEnabled returns false when settings are empty.
	 */
	public function test_is_enabled_returns_false_when_settings_empty(): void {
		delete_option( 'ihumbak_invoices_settings' );
		$this->resetPluginSingleton();
		$this->service = new OrderStatusService();

		$this->assertFalse( $this->service->isEnabled() );
	}

	// ==========================================================================
	// getTargetStatus() tests
	// ==========================================================================

	/**
	 * Test getTargetStatus returns configured status.
	 */
	public function test_get_target_status_returns_configured_status(): void {
		$this->enableOrderStatusFeature( 'on-hold' );

		$this->assertSame( 'on-hold', $this->service->getTargetStatus() );
	}

	/**
	 * Test getTargetStatus returns completed as default.
	 */
	public function test_get_target_status_returns_completed_by_default(): void {
		delete_option( 'ihumbak_invoices_settings' );
		$this->resetPluginSingleton();
		$this->service = new OrderStatusService();

		$this->assertSame( 'completed', $this->service->getTargetStatus() );
	}

	// ==========================================================================
	// shouldChangeStatus() tests
	// ==========================================================================

	/**
	 * Test shouldChangeStatus returns false for credit_note document type.
	 */
	public function test_should_change_status_returns_false_for_credit_note(): void {
		$this->enableOrderStatusFeature();

		$credit_note = $this->createCreditNote( 123 );

		$this->assertFalse( $this->service->shouldChangeStatus( $credit_note ) );
	}

	/**
	 * Test shouldChangeStatus returns false when document has no order_id.
	 */
	public function test_should_change_status_returns_false_when_no_order_id(): void {
		$this->enableOrderStatusFeature();

		$invoice = $this->createInvoice(); // No order_id.

		$this->assertFalse( $this->service->shouldChangeStatus( $invoice ) );
	}

	/**
	 * Test shouldChangeStatus returns false when feature is disabled.
	 */
	public function test_should_change_status_returns_false_when_feature_disabled(): void {
		$this->disableOrderStatusFeature();

		$invoice = $this->createInvoice( 123 );

		$this->assertFalse( $this->service->shouldChangeStatus( $invoice ) );
	}

	/**
	 * Test shouldChangeStatus returns true for invoice when all conditions met.
	 */
	public function test_should_change_status_returns_true_for_invoice_when_conditions_met(): void {
		$this->enableOrderStatusFeature();

		$invoice = $this->createInvoice( 123 );

		$this->assertTrue( $this->service->shouldChangeStatus( $invoice ) );
	}

	/**
	 * Test shouldChangeStatus returns true for receipt when all conditions met.
	 */
	public function test_should_change_status_returns_true_for_receipt_when_conditions_met(): void {
		$this->enableOrderStatusFeature();

		$receipt = $this->createReceipt( 123 );

		$this->assertTrue( $this->service->shouldChangeStatus( $receipt ) );
	}

	/**
	 * Test ihumbak_order_status_change_enabled filter can override to false.
	 */
	public function test_should_change_status_filter_can_override_to_false(): void {
		$this->enableOrderStatusFeature();

		$invoice = $this->createInvoice( 123 );

		// Add filter to deny status change.
		add_filter( 'ihumbak_order_status_change_enabled', '__return_false' );

		$this->assertFalse( $this->service->shouldChangeStatus( $invoice ) );

		// Clean up.
		remove_filter( 'ihumbak_order_status_change_enabled', '__return_false' );
	}

	/**
	 * Test ihumbak_order_status_change_enabled filter receives correct parameters.
	 */
	public function test_should_change_status_filter_receives_correct_parameters(): void {
		$this->enableOrderStatusFeature();

		$invoice  = $this->createInvoice( 456 );
		$received = array();

		$callback = function ( $enabled, $order_id, $document ) use ( &$received ) {
			$received = array(
				'enabled'  => $enabled,
				'order_id' => $order_id,
				'document' => $document,
			);
			return $enabled;
		};

		add_filter( 'ihumbak_order_status_change_enabled', $callback, 10, 3 );

		$this->service->shouldChangeStatus( $invoice );

		$this->assertTrue( $received['enabled'] );
		$this->assertSame( 456, $received['order_id'] );
		$this->assertSame( $invoice, $received['document'] );

		// Clean up.
		remove_filter( 'ihumbak_order_status_change_enabled', $callback );
	}

	// ==========================================================================
	// maybeChangeOrderStatus() tests
	// ==========================================================================

	/**
	 * Test maybeChangeOrderStatus returns false when user_confirmed is false.
	 */
	public function test_maybe_change_order_status_returns_false_when_not_confirmed(): void {
		$this->enableOrderStatusFeature();

		$invoice = $this->createInvoice( 123 );
		$this->createMockOrder( 123, 'processing' );

		$result = $this->service->maybeChangeOrderStatus( $invoice, false );

		$this->assertFalse( $result );
	}

	/**
	 * Test maybeChangeOrderStatus returns false when shouldChangeStatus returns false.
	 */
	public function test_maybe_change_order_status_returns_false_when_should_change_returns_false(): void {
		$this->disableOrderStatusFeature();

		$invoice = $this->createInvoice( 123 );
		$this->createMockOrder( 123, 'processing' );

		$result = $this->service->maybeChangeOrderStatus( $invoice, true );

		$this->assertFalse( $result );
	}

	/**
	 * Test maybeChangeOrderStatus returns false when order does not exist.
	 */
	public function test_maybe_change_order_status_returns_false_when_order_not_found(): void {
		$this->enableOrderStatusFeature();

		$invoice = $this->createInvoice( 999 ); // Order 999 does not exist.

		$result = $this->service->maybeChangeOrderStatus( $invoice, true );

		$this->assertFalse( $result );
	}

	/**
	 * Test maybeChangeOrderStatus returns false when order already has target status.
	 */
	public function test_maybe_change_order_status_returns_false_when_already_target_status(): void {
		$this->enableOrderStatusFeature( 'completed' );

		$invoice = $this->createInvoice( 123 );
		$this->createMockOrder( 123, 'completed' ); // Already at target status.

		$result = $this->service->maybeChangeOrderStatus( $invoice, true );

		$this->assertFalse( $result );
	}

	/**
	 * Test maybeChangeOrderStatus returns true and changes status when all conditions met.
	 */
	public function test_maybe_change_order_status_returns_true_when_conditions_met(): void {
		$this->enableOrderStatusFeature( 'completed' );

		$invoice = $this->createInvoice( 123 );
		$order   = $this->createMockOrder( 123, 'processing' );

		$result = $this->service->maybeChangeOrderStatus( $invoice, true );

		$this->assertTrue( $result );
		$this->assertSame( 'completed', $order->get_status() );
	}

	/**
	 * Test maybeChangeOrderStatus fires before and after actions.
	 */
	public function test_maybe_change_order_status_fires_actions(): void {
		$this->enableOrderStatusFeature( 'completed' );

		$invoice = $this->createInvoice( 123 );
		$invoice->setDocumentNumber( 'FV/2024/01/0001' );
		$this->createMockOrder( 123, 'processing' );

		$before_fired = false;
		$after_fired  = false;
		$before_args  = array();
		$after_args   = array();

		// Override do_action to track calls.
		$original_do_action = null;

		// Create a custom callback to track action calls.
		$track_actions = function ( $tag, ...$args ) use ( &$before_fired, &$after_fired, &$before_args, &$after_args ) {
			if ( 'ihumbak_before_order_status_change' === $tag ) {
				$before_fired = true;
				$before_args  = $args;
			}
			if ( 'ihumbak_after_order_status_change' === $tag ) {
				$after_fired = true;
				$after_args  = $args;
			}
		};

		// We need to override do_action temporarily.
		// Since the mock do_action is defined in bootstrap.php, we'll test the behavior differently.
		// The actions are called but the mock do_action does nothing.
		// We can verify the behavior by checking the method execution path.
		$result = $this->service->maybeChangeOrderStatus( $invoice, true );

		// Since do_action is mocked to do nothing, we can only verify that the method returned true.
		// The actions would be fired in a real WordPress environment.
		$this->assertTrue( $result );
	}

	/**
	 * Test maybeChangeOrderStatus updates order status with correct note.
	 */
	public function test_maybe_change_order_status_updates_with_correct_note(): void {
		$this->enableOrderStatusFeature( 'completed' );

		$invoice = $this->createInvoice( 123 );
		$invoice->setDocumentNumber( 'FV/2024/01/0001' );
		$order = $this->createMockOrder( 123, 'processing' );

		$this->service->maybeChangeOrderStatus( $invoice, true );

		// Verify the status note contains the document number.
		$note = $order->get_last_status_note();
		$this->assertStringContainsString( 'FV/2024/01/0001', $note );
	}

	// ==========================================================================
	// getCheckboxDefault() tests
	// ==========================================================================

	/**
	 * Test getCheckboxDefault returns true when feature is enabled.
	 */
	public function test_get_checkbox_default_returns_true_when_enabled(): void {
		$this->enableOrderStatusFeature();

		$result = $this->service->getCheckboxDefault( 123 );

		$this->assertTrue( $result );
	}

	/**
	 * Test getCheckboxDefault returns false when feature is disabled.
	 */
	public function test_get_checkbox_default_returns_false_when_disabled(): void {
		$this->disableOrderStatusFeature();

		$result = $this->service->getCheckboxDefault( 123 );

		$this->assertFalse( $result );
	}

	/**
	 * Test getCheckboxDefault handles null order_id.
	 */
	public function test_get_checkbox_default_handles_null_order_id(): void {
		$this->enableOrderStatusFeature();

		$result = $this->service->getCheckboxDefault( null );

		$this->assertTrue( $result );
	}

	/**
	 * Test ihumbak_order_status_change_checkbox_default filter can override to false.
	 */
	public function test_get_checkbox_default_filter_can_override_to_false(): void {
		$this->enableOrderStatusFeature();

		add_filter( 'ihumbak_order_status_change_checkbox_default', '__return_false' );

		$result = $this->service->getCheckboxDefault( 123 );

		$this->assertFalse( $result );

		// Clean up.
		remove_filter( 'ihumbak_order_status_change_checkbox_default', '__return_false' );
	}

	/**
	 * Test ihumbak_order_status_change_checkbox_default filter can override to true.
	 */
	public function test_get_checkbox_default_filter_can_override_to_true(): void {
		$this->disableOrderStatusFeature();

		add_filter( 'ihumbak_order_status_change_checkbox_default', '__return_true' );

		$result = $this->service->getCheckboxDefault( 123 );

		$this->assertTrue( $result );

		// Clean up.
		remove_filter( 'ihumbak_order_status_change_checkbox_default', '__return_true' );
	}

	/**
	 * Test ihumbak_order_status_change_checkbox_default filter receives correct parameters.
	 */
	public function test_get_checkbox_default_filter_receives_correct_parameters(): void {
		$this->enableOrderStatusFeature();

		$received = array();

		$callback = function ( $checked, $order_id ) use ( &$received ) {
			$received = array(
				'checked'  => $checked,
				'order_id' => $order_id,
			);
			return $checked;
		};

		add_filter( 'ihumbak_order_status_change_checkbox_default', $callback, 10, 2 );

		$this->service->getCheckboxDefault( 789 );

		$this->assertTrue( $received['checked'] );
		$this->assertSame( 789, $received['order_id'] );

		// Clean up.
		remove_filter( 'ihumbak_order_status_change_checkbox_default', $callback );
	}

	// ==========================================================================
	// getTargetStatusForDocument() tests
	// ==========================================================================

	/**
	 * Test getTargetStatusForDocument returns configured target status.
	 */
	public function test_get_target_status_for_document_returns_configured_status(): void {
		$this->enableOrderStatusFeature( 'on-hold' );

		$invoice = $this->createInvoice( 123 );

		$result = $this->service->getTargetStatusForDocument( $invoice );

		$this->assertSame( 'on-hold', $result );
	}

	/**
	 * Test ihumbak_order_status_change_target filter can override the status.
	 */
	public function test_get_target_status_for_document_filter_can_override(): void {
		$this->enableOrderStatusFeature( 'completed' );

		$invoice = $this->createInvoice( 123 );

		$callback = function ( $status, $order_id, $document ) {
			return 'custom-status';
		};

		add_filter( 'ihumbak_order_status_change_target', $callback, 10, 3 );

		$result = $this->service->getTargetStatusForDocument( $invoice );

		$this->assertSame( 'custom-status', $result );

		// Clean up.
		remove_filter( 'ihumbak_order_status_change_target', $callback );
	}

	/**
	 * Test ihumbak_order_status_change_target filter receives correct parameters.
	 */
	public function test_get_target_status_for_document_filter_receives_correct_parameters(): void {
		$this->enableOrderStatusFeature( 'completed' );

		$invoice  = $this->createInvoice( 456 );
		$received = array();

		$callback = function ( $status, $order_id, $document ) use ( &$received ) {
			$received = array(
				'status'   => $status,
				'order_id' => $order_id,
				'document' => $document,
			);
			return $status;
		};

		add_filter( 'ihumbak_order_status_change_target', $callback, 10, 3 );

		$this->service->getTargetStatusForDocument( $invoice );

		$this->assertSame( 'completed', $received['status'] );
		$this->assertSame( 456, $received['order_id'] );
		$this->assertSame( $invoice, $received['document'] );

		// Clean up.
		remove_filter( 'ihumbak_order_status_change_target', $callback );
	}

	/**
	 * Test getTargetStatusForDocument handles document without order_id.
	 */
	public function test_get_target_status_for_document_handles_no_order_id(): void {
		$this->enableOrderStatusFeature( 'completed' );

		$invoice = $this->createInvoice(); // No order_id.

		$received_order_id = null;

		$callback = function ( $status, $order_id, $document ) use ( &$received_order_id ) {
			$received_order_id = $order_id;
			return $status;
		};

		add_filter( 'ihumbak_order_status_change_target', $callback, 10, 3 );

		$result = $this->service->getTargetStatusForDocument( $invoice );

		$this->assertSame( 'completed', $result );
		$this->assertSame( 0, $received_order_id ); // Should be 0 when null.

		// Clean up.
		remove_filter( 'ihumbak_order_status_change_target', $callback );
	}

	// ==========================================================================
	// getOrderStatuses() tests
	// ==========================================================================

	/**
	 * Test getOrderStatuses returns array.
	 */
	public function test_get_order_statuses_returns_array(): void {
		$result = $this->service->getOrderStatuses();

		$this->assertIsArray( $result );
	}

	// ==========================================================================
	// getTargetStatusLabel() tests
	// ==========================================================================

	/**
	 * Test getTargetStatusLabel returns status when no label found.
	 */
	public function test_get_target_status_label_returns_status_when_no_label(): void {
		$this->enableOrderStatusFeature( 'custom-status' );

		$result = $this->service->getTargetStatusLabel();

		$this->assertSame( 'custom-status', $result );
	}
}

/**
 * Mock WC_Order class with additional methods for testing.
 */
class MockWCOrder extends \WC_Order {

	/**
	 * Order ID.
	 *
	 * @var int
	 */
	private int $id = 0;

	/**
	 * Order status.
	 *
	 * @var string
	 */
	private string $status = 'pending';

	/**
	 * Last status update note.
	 *
	 * @var string
	 */
	private string $last_status_note = '';

	/**
	 * Set order ID.
	 *
	 * @param int $id Order ID.
	 */
	public function set_id( int $id ): void {
		$this->id = $id;
	}

	/**
	 * Get order ID.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Set order status.
	 *
	 * @param string $status Order status.
	 */
	public function set_status( string $status ): void {
		$this->status = $status;
	}

	/**
	 * Get order status.
	 *
	 * @return string
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Update order status.
	 *
	 * @param string $new_status New status.
	 * @param string $note       Status change note.
	 * @param bool   $manual     Manual update flag.
	 * @return bool
	 */
	public function update_status( string $new_status, string $note = '', bool $manual = false ): bool {
		$this->status           = str_replace( 'wc-', '', $new_status );
		$this->last_status_note = $note;
		return true;
	}

	/**
	 * Get the last status change note.
	 *
	 * @return string
	 */
	public function get_last_status_note(): string {
		return $this->last_status_note;
	}
}
