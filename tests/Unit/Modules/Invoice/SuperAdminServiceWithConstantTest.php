<?php
/**
 * SuperAdminService unit tests with IHUMBAK_SUPER_ADMIN_IDS constant defined.
 *
 * These tests run in a separate process to allow defining the constant.
 *
 * @package IHumbak\Invoices\Tests\Unit\Modules\Invoice
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Modules\Invoice;

use IHumbak\Invoices\Models\Document;
use IHumbak\Invoices\Modules\Invoice\SuperAdminService;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SuperAdminService with constant defined.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SuperAdminServiceWithConstantTest extends TestCase {

	/**
	 * Service under test.
	 *
	 * @var SuperAdminService
	 */
	private SuperAdminService $service;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Define the constant for testing.
		if ( ! defined( 'IHUMBAK_SUPER_ADMIN_IDS' ) ) {
			define( 'IHUMBAK_SUPER_ADMIN_IDS', '1,5,12' );
		}

		$this->service = new SuperAdminService();
	}

	// ==========================================================================
	// getSuperAdminIds() positive tests
	// ==========================================================================

	/**
	 * Test parsing multiple IDs from constant.
	 */
	public function test_get_super_admin_ids_parses_multiple_ids(): void {
		$result = $this->service->getSuperAdminIds();

		$this->assertIsArray( $result );
		$this->assertCount( 3, $result );
		$this->assertContains( 1, $result );
		$this->assertContains( 5, $result );
		$this->assertContains( 12, $result );
	}

	/**
	 * Test that parsed IDs are integers.
	 */
	public function test_get_super_admin_ids_returns_integers(): void {
		$result = $this->service->getSuperAdminIds();

		foreach ( $result as $id ) {
			$this->assertIsInt( $id );
		}
	}

	// ==========================================================================
	// isUserSuperAdmin() positive tests
	// ==========================================================================

	/**
	 * Test user in super-admin list returns true.
	 */
	public function test_is_user_super_admin_returns_true_for_user_in_list(): void {
		$this->assertTrue( $this->service->isUserSuperAdmin( 1 ) );
		$this->assertTrue( $this->service->isUserSuperAdmin( 5 ) );
		$this->assertTrue( $this->service->isUserSuperAdmin( 12 ) );
	}

	/**
	 * Test user not in super-admin list returns false.
	 */
	public function test_is_user_super_admin_returns_false_for_user_not_in_list(): void {
		$this->assertFalse( $this->service->isUserSuperAdmin( 2 ) );
		$this->assertFalse( $this->service->isUserSuperAdmin( 999 ) );
	}

	// ==========================================================================
	// canRevertToDraft() positive tests
	// ==========================================================================

	/**
	 * Test super-admin can revert issued document.
	 */
	public function test_can_revert_to_draft_returns_true_for_super_admin_with_issued(): void {
		$this->assertTrue( $this->service->canRevertToDraft( 1, Document::STATUS_ISSUED ) );
		$this->assertTrue( $this->service->canRevertToDraft( 5, Document::STATUS_ISSUED ) );
		$this->assertTrue( $this->service->canRevertToDraft( 12, Document::STATUS_ISSUED ) );
	}

	/**
	 * Test super-admin can revert sent document.
	 */
	public function test_can_revert_to_draft_returns_true_for_super_admin_with_sent(): void {
		$this->assertTrue( $this->service->canRevertToDraft( 1, Document::STATUS_SENT ) );
	}

	/**
	 * Test super-admin can revert paid document.
	 */
	public function test_can_revert_to_draft_returns_true_for_super_admin_with_paid(): void {
		$this->assertTrue( $this->service->canRevertToDraft( 1, Document::STATUS_PAID ) );
	}

	/**
	 * Test super-admin cannot revert draft document.
	 */
	public function test_can_revert_to_draft_returns_false_for_super_admin_with_draft(): void {
		$this->assertFalse( $this->service->canRevertToDraft( 1, Document::STATUS_DRAFT ) );
	}

	/**
	 * Test super-admin cannot revert cancelled document.
	 */
	public function test_can_revert_to_draft_returns_false_for_super_admin_with_cancelled(): void {
		$this->assertFalse( $this->service->canRevertToDraft( 1, Document::STATUS_CANCELLED ) );
	}

	/**
	 * Test non-super-admin cannot revert issued document.
	 */
	public function test_can_revert_to_draft_returns_false_for_non_super_admin(): void {
		$this->assertFalse( $this->service->canRevertToDraft( 999, Document::STATUS_ISSUED ) );
	}
}
