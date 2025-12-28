<?php
/**
 * PermissionService unit tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Modules\Invoice
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Modules\Invoice;

use IHumbak\Invoices\Modules\Invoice\PermissionService;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PermissionService.
 */
class PermissionServiceTest extends TestCase {

	/**
	 * Service under test.
	 *
	 * @var PermissionService
	 */
	private PermissionService $service;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->service = new PermissionService();
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		// Clean up option after each test.
		delete_option( PermissionService::OPTION_KEY );

		// Clean up mock filters.
		global $mock_wp_filters;
		$mock_wp_filters = array();

		// Clean up mock user capabilities.
		global $mock_wp_user_capabilities;
		$mock_wp_user_capabilities = array();

		parent::tearDown();
	}

	// ==========================================================================
	// getMinimumCapability() tests
	// ==========================================================================

	/**
	 * Test default capability is manage_woocommerce.
	 */
	public function test_get_minimum_capability_returns_manage_woocommerce_by_default(): void {
		$result = $this->service->getMinimumCapability();
		$this->assertSame( 'manage_woocommerce', $result );
	}

	/**
	 * Test administrator role returns manage_options.
	 */
	public function test_get_minimum_capability_returns_manage_options_for_administrator(): void {
		update_option(
			PermissionService::OPTION_KEY,
			array(
				'permissions' => array(
					'minimum_role' => 'administrator',
				),
			)
		);

		$result = $this->service->getMinimumCapability();
		$this->assertSame( 'manage_options', $result );
	}

	/**
	 * Test shop_manager role returns manage_woocommerce.
	 */
	public function test_get_minimum_capability_returns_manage_woocommerce_for_shop_manager(): void {
		update_option(
			PermissionService::OPTION_KEY,
			array(
				'permissions' => array(
					'minimum_role' => 'shop_manager',
				),
			)
		);

		$result = $this->service->getMinimumCapability();
		$this->assertSame( 'manage_woocommerce', $result );
	}

	/**
	 * Test unknown role falls back to manage_woocommerce.
	 */
	public function test_get_minimum_capability_falls_back_for_unknown_role(): void {
		update_option(
			PermissionService::OPTION_KEY,
			array(
				'permissions' => array(
					'minimum_role' => 'unknown_role',
				),
			)
		);

		$result = $this->service->getMinimumCapability();
		$this->assertSame( 'manage_woocommerce', $result );
	}

	/**
	 * Test missing permissions key falls back to manage_woocommerce.
	 */
	public function test_get_minimum_capability_falls_back_when_permissions_missing(): void {
		update_option(
			PermissionService::OPTION_KEY,
			array(
				'seller' => array( 'name' => 'Test Company' ),
			)
		);

		$result = $this->service->getMinimumCapability();
		$this->assertSame( 'manage_woocommerce', $result );
	}

	/**
	 * Test empty option falls back to manage_woocommerce.
	 */
	public function test_get_minimum_capability_falls_back_when_option_is_empty(): void {
		update_option( PermissionService::OPTION_KEY, array() );

		$result = $this->service->getMinimumCapability();
		$this->assertSame( 'manage_woocommerce', $result );
	}

	// ==========================================================================
	// getAvailableRoles() tests
	// ==========================================================================

	/**
	 * Test available roles contains expected options.
	 */
	public function test_get_available_roles_returns_expected_roles(): void {
		$roles = PermissionService::getAvailableRoles();

		$this->assertIsArray( $roles );
		$this->assertArrayHasKey( 'administrator', $roles );
		$this->assertArrayHasKey( 'shop_manager', $roles );
		$this->assertCount( 2, $roles );
	}

	/**
	 * Test available roles labels are strings.
	 */
	public function test_get_available_roles_labels_are_strings(): void {
		$roles = PermissionService::getAvailableRoles();

		foreach ( $roles as $role => $label ) {
			$this->assertIsString( $role );
			$this->assertIsString( $label );
		}
	}

	// ==========================================================================
	// isValidRole() tests
	// ==========================================================================

	/**
	 * Test administrator is a valid role.
	 */
	public function test_is_valid_role_returns_true_for_administrator(): void {
		$result = PermissionService::isValidRole( 'administrator' );
		$this->assertTrue( $result );
	}

	/**
	 * Test shop_manager is a valid role.
	 */
	public function test_is_valid_role_returns_true_for_shop_manager(): void {
		$result = PermissionService::isValidRole( 'shop_manager' );
		$this->assertTrue( $result );
	}

	/**
	 * Test unknown role is not valid.
	 */
	public function test_is_valid_role_returns_false_for_unknown_role(): void {
		$result = PermissionService::isValidRole( 'unknown_role' );
		$this->assertFalse( $result );
	}

	/**
	 * Test editor role is not valid.
	 */
	public function test_is_valid_role_returns_false_for_editor(): void {
		$result = PermissionService::isValidRole( 'editor' );
		$this->assertFalse( $result );
	}

	/**
	 * Test empty string is not valid.
	 */
	public function test_is_valid_role_returns_false_for_empty_string(): void {
		$result = PermissionService::isValidRole( '' );
		$this->assertFalse( $result );
	}

	// ==========================================================================
	// Constants tests
	// ==========================================================================

	/**
	 * Test default role constant is correct.
	 */
	public function test_default_role_constant_is_shop_manager(): void {
		$this->assertSame( 'shop_manager', PermissionService::DEFAULT_ROLE );
	}

	/**
	 * Test settings capability constant is correct.
	 */
	public function test_settings_capability_constant_is_manage_options(): void {
		$this->assertSame( 'manage_options', PermissionService::SETTINGS_CAPABILITY );
	}

	/**
	 * Test option key constant is correct.
	 */
	public function test_option_key_constant_is_correct(): void {
		$this->assertSame( 'ihumbak_invoices_settings', PermissionService::OPTION_KEY );
	}

	// ==========================================================================
	// canManageDocuments() tests
	// ==========================================================================

	/**
	 * Test canManageDocuments returns false when user lacks capability.
	 */
	public function test_can_manage_documents_returns_false_when_no_capability(): void {
		// By default, no user is logged in, so current_user_can returns false.
		$result = $this->service->canManageDocuments();
		$this->assertFalse( $result );
	}

	/**
	 * Test canManageDocuments filter can override the result to true.
	 */
	public function test_can_manage_documents_filter_can_grant_access(): void {
		// Add filter to grant access.
		add_filter( 'ihumbak_can_manage_documents', '__return_true' );

		$result = $this->service->canManageDocuments();
		$this->assertTrue( $result );

		// Clean up.
		remove_filter( 'ihumbak_can_manage_documents', '__return_true' );
	}

	/**
	 * Test canManageDocuments filter can override the result to false.
	 */
	public function test_can_manage_documents_filter_can_deny_access(): void {
		// First grant access via another filter, then deny it.
		add_filter( 'ihumbak_can_manage_documents', '__return_false' );

		$result = $this->service->canManageDocuments();
		$this->assertFalse( $result );

		// Clean up.
		remove_filter( 'ihumbak_can_manage_documents', '__return_false' );
	}

	/**
	 * Test canManageDocuments filter receives correct parameters.
	 */
	public function test_can_manage_documents_filter_receives_correct_parameters(): void {
		$received_params = array();

		$callback = function ( $can_manage, $capability, $user_id ) use ( &$received_params ) {
			$received_params = array(
				'can_manage' => $can_manage,
				'capability' => $capability,
				'user_id'    => $user_id,
			);
			return $can_manage;
		};

		add_filter( 'ihumbak_can_manage_documents', $callback, 10, 3 );

		$this->service->canManageDocuments();

		// Verify parameters.
		$this->assertArrayHasKey( 'can_manage', $received_params );
		$this->assertArrayHasKey( 'capability', $received_params );
		$this->assertArrayHasKey( 'user_id', $received_params );
		$this->assertIsBool( $received_params['can_manage'] );
		$this->assertSame( 'manage_woocommerce', $received_params['capability'] );
		$this->assertIsInt( $received_params['user_id'] );

		// Clean up.
		remove_filter( 'ihumbak_can_manage_documents', $callback );
	}

	/**
	 * Test canManageDocuments filter receives administrator capability when configured.
	 */
	public function test_can_manage_documents_filter_receives_configured_capability(): void {
		update_option(
			PermissionService::OPTION_KEY,
			array(
				'permissions' => array(
					'minimum_role' => 'administrator',
				),
			)
		);

		$received_capability = null;

		$callback = function ( $can_manage, $capability ) use ( &$received_capability ) {
			$received_capability = $capability;
			return $can_manage;
		};

		add_filter( 'ihumbak_can_manage_documents', $callback, 10, 3 );

		$this->service->canManageDocuments();

		$this->assertSame( 'manage_options', $received_capability );

		// Clean up.
		remove_filter( 'ihumbak_can_manage_documents', $callback );
	}

	// ==========================================================================
	// canAccessSettings() tests
	// ==========================================================================

	/**
	 * Test canAccessSettings returns false when user lacks capability.
	 */
	public function test_can_access_settings_returns_false_when_no_capability(): void {
		// By default, no user is logged in.
		$result = $this->service->canAccessSettings();
		$this->assertFalse( $result );
	}

	/**
	 * Test canAccessSettings always requires manage_options regardless of configured role.
	 */
	public function test_can_access_settings_always_requires_manage_options(): void {
		// Even if minimum_role is shop_manager, settings require manage_options.
		update_option(
			PermissionService::OPTION_KEY,
			array(
				'permissions' => array(
					'minimum_role' => 'shop_manager',
				),
			)
		);

		// No user logged in, so should be false.
		$result = $this->service->canAccessSettings();
		$this->assertFalse( $result );

		// Verify the constant is correct.
		$this->assertSame( 'manage_options', PermissionService::SETTINGS_CAPABILITY );
	}
}
