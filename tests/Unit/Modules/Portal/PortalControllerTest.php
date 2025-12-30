<?php
/**
 * PortalController unit tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Modules\Portal
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Modules\Portal;

use IHumbak\Invoices\Models\Invoice;
use IHumbak\Invoices\Models\Document;
use IHumbak\Invoices\Modules\Portal\PortalController;
use IHumbak\Invoices\Modules\Portal\PortalService;
use IHumbak\Invoices\Infrastructure\Database\DocumentRepository;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PortalController.
 */
class PortalControllerTest extends TestCase {

	/**
	 * Mock portal service.
	 *
	 * @var PortalService|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $service_mock;

	/**
	 * Mock document repository.
	 *
	 * @var DocumentRepository|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $repository_mock;

	/**
	 * Controller under test.
	 *
	 * @var PortalController
	 */
	private PortalController $controller;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->service_mock    = $this->createMock( PortalService::class );
		$this->repository_mock = $this->createMock( DocumentRepository::class );
		$this->controller      = new PortalController( $this->service_mock, $this->repository_mock );
	}

	// ==========================================================================
	// Constants tests
	// ==========================================================================

	/**
	 * Test endpoint slug constant is correct.
	 */
	public function test_endpoint_slug_constant(): void {
		$this->assertSame( 'invoices', PortalController::ENDPOINT_SLUG );
	}

	/**
	 * Test download action constant is correct.
	 */
	public function test_download_action_constant(): void {
		$this->assertSame( 'ihumbak_customer_download_pdf', PortalController::DOWNLOAD_ACTION );
	}

	// ==========================================================================
	// add_query_vars() tests
	// ==========================================================================

	/**
	 * Test add_query_vars adds endpoint slug to query vars.
	 */
	public function test_add_query_vars_adds_endpoint(): void {
		$vars = array( 'existing_var' );

		$result = $this->controller->add_query_vars( $vars );

		$this->assertContains( 'existing_var', $result );
		$this->assertContains( PortalController::ENDPOINT_SLUG, $result );
	}

	/**
	 * Test add_query_vars preserves existing vars.
	 */
	public function test_add_query_vars_preserves_existing(): void {
		$vars = array( 'var1', 'var2', 'var3' );

		$result = $this->controller->add_query_vars( $vars );

		$this->assertCount( 4, $result );
		$this->assertContains( 'var1', $result );
		$this->assertContains( 'var2', $result );
		$this->assertContains( 'var3', $result );
	}

	/**
	 * Test add_query_vars works with empty array.
	 */
	public function test_add_query_vars_works_with_empty_array(): void {
		$result = $this->controller->add_query_vars( array() );

		$this->assertCount( 1, $result );
		$this->assertContains( PortalController::ENDPOINT_SLUG, $result );
	}

	// ==========================================================================
	// add_menu_item() tests
	// ==========================================================================

	/**
	 * Test add_menu_item adds invoices before customer-logout.
	 */
	public function test_add_menu_item_inserts_before_logout(): void {
		$items = array(
			'dashboard'       => 'Dashboard',
			'orders'          => 'Orders',
			'customer-logout' => 'Logout',
		);

		$result = $this->controller->add_menu_item( $items );

		$keys = array_keys( $result );

		$invoices_pos = array_search( PortalController::ENDPOINT_SLUG, $keys, true );
		$logout_pos   = array_search( 'customer-logout', $keys, true );

		$this->assertNotFalse( $invoices_pos );
		$this->assertNotFalse( $logout_pos );
		$this->assertLessThan( $logout_pos, $invoices_pos );
	}

	/**
	 * Test add_menu_item preserves existing items.
	 */
	public function test_add_menu_item_preserves_existing(): void {
		$items = array(
			'dashboard'       => 'Dashboard',
			'orders'          => 'Orders',
			'customer-logout' => 'Logout',
		);

		$result = $this->controller->add_menu_item( $items );

		$this->assertArrayHasKey( 'dashboard', $result );
		$this->assertArrayHasKey( 'orders', $result );
		$this->assertArrayHasKey( 'customer-logout', $result );
		$this->assertSame( 'Dashboard', $result['dashboard'] );
		$this->assertSame( 'Orders', $result['orders'] );
		$this->assertSame( 'Logout', $result['customer-logout'] );
	}

	/**
	 * Test add_menu_item adds at end if no logout present.
	 */
	public function test_add_menu_item_adds_at_end_when_no_logout(): void {
		$items = array(
			'dashboard' => 'Dashboard',
			'orders'    => 'Orders',
		);

		$result = $this->controller->add_menu_item( $items );

		$this->assertArrayHasKey( PortalController::ENDPOINT_SLUG, $result );
		$this->assertCount( 3, $result );
	}

	/**
	 * Test add_menu_item returns correct count.
	 */
	public function test_add_menu_item_returns_correct_count(): void {
		$items = array(
			'dashboard'       => 'Dashboard',
			'orders'          => 'Orders',
			'customer-logout' => 'Logout',
		);

		$result = $this->controller->add_menu_item( $items );

		$this->assertCount( 4, $result );
	}

	/**
	 * Test add_menu_item label is translatable.
	 */
	public function test_add_menu_item_has_label(): void {
		$items = array(
			'customer-logout' => 'Logout',
		);

		$result = $this->controller->add_menu_item( $items );

		$this->assertIsString( $result[ PortalController::ENDPOINT_SLUG ] );
		$this->assertNotEmpty( $result[ PortalController::ENDPOINT_SLUG ] );
	}

	// ==========================================================================
	// get_download_url() tests
	// ==========================================================================

	/**
	 * Test get_download_url includes document ID.
	 */
	public function test_get_download_url_includes_document_id(): void {
		$invoice = Invoice::fromArray(
			array(
				'id'              => 42,
				'order_id'        => 123,
				'document_type'   => 'invoice',
				'document_number' => 'FV/2024/12/0001',
				'status'          => Document::STATUS_ISSUED,
			)
		);

		$url = $this->controller->get_download_url( $invoice );

		$this->assertStringContainsString( 'document_id=42', $url );
	}

	/**
	 * Test get_download_url includes action parameter.
	 */
	public function test_get_download_url_includes_action(): void {
		$invoice = Invoice::fromArray(
			array(
				'id'              => 1,
				'order_id'        => 123,
				'document_type'   => 'invoice',
				'document_number' => 'FV/2024/12/0001',
				'status'          => Document::STATUS_ISSUED,
			)
		);

		$url = $this->controller->get_download_url( $invoice );

		$this->assertStringContainsString( 'action=' . PortalController::DOWNLOAD_ACTION, $url );
	}

	/**
	 * Test get_download_url includes nonce.
	 */
	public function test_get_download_url_includes_nonce(): void {
		$invoice = Invoice::fromArray(
			array(
				'id'              => 1,
				'order_id'        => 123,
				'document_type'   => 'invoice',
				'document_number' => 'FV/2024/12/0001',
				'status'          => Document::STATUS_ISSUED,
			)
		);

		$url = $this->controller->get_download_url( $invoice );

		$this->assertStringContainsString( '_wpnonce=', $url );
	}

	/**
	 * Test get_download_url returns valid URL.
	 */
	public function test_get_download_url_returns_valid_url(): void {
		$invoice = Invoice::fromArray(
			array(
				'id'              => 1,
				'order_id'        => 123,
				'document_type'   => 'invoice',
				'document_number' => 'FV/2024/12/0001',
				'status'          => Document::STATUS_ISSUED,
			)
		);

		$url = $this->controller->get_download_url( $invoice );

		$this->assertIsString( $url );
		$this->assertNotEmpty( $url );
	}

	// ==========================================================================
	// Constructor tests
	// ==========================================================================

	/**
	 * Test controller can be instantiated with mocks.
	 */
	public function test_controller_can_be_instantiated_with_mocks(): void {
		$controller = new PortalController( $this->service_mock, $this->repository_mock );

		$this->assertInstanceOf( PortalController::class, $controller );
	}
}
