<?php
/**
 * SuperAdminService unit tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Modules\Invoice
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Modules\Invoice;

use IHumbak\Invoices\Models\Document;
use IHumbak\Invoices\Modules\Invoice\SuperAdminService;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SuperAdminService.
 */
class SuperAdminServiceTest extends TestCase {

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
		$this->service = new SuperAdminService();
	}

	// ==========================================================================
	// getSuperAdminIds() tests
	// ==========================================================================

	/**
	 * Test returns empty array when constant is not defined.
	 */
	public function test_get_super_admin_ids_returns_empty_when_constant_not_defined(): void {
		// Note: This test may fail if constant is already defined.
		// We're testing the behavior when constant is not defined.
		if ( defined( 'IHUMBAK_SUPER_ADMIN_IDS' ) ) {
			$this->markTestSkipped( 'Constant IHUMBAK_SUPER_ADMIN_IDS already defined, cannot test undefined behavior.' );
		}

		$result = $this->service->getSuperAdminIds();
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	// ==========================================================================
	// isUserSuperAdmin() tests
	// ==========================================================================

	/**
	 * Test zero user ID returns false.
	 */
	public function test_is_user_super_admin_returns_false_for_zero(): void {
		$result = $this->service->isUserSuperAdmin( 0 );
		$this->assertFalse( $result );
	}

	/**
	 * Test negative user ID returns false.
	 */
	public function test_is_user_super_admin_returns_false_for_negative(): void {
		$result = $this->service->isUserSuperAdmin( -1 );
		$this->assertFalse( $result );
	}

	/**
	 * Test user not in empty super-admin list returns false.
	 */
	public function test_is_user_super_admin_returns_false_when_list_is_empty(): void {
		if ( defined( 'IHUMBAK_SUPER_ADMIN_IDS' ) ) {
			$this->markTestSkipped( 'Constant already defined.' );
		}

		$result = $this->service->isUserSuperAdmin( 1 );
		$this->assertFalse( $result );
	}

	// ==========================================================================
	// canRevertToDraft() tests
	// ==========================================================================

	/**
	 * Test cannot revert draft document.
	 */
	public function test_can_revert_to_draft_returns_false_for_draft_status(): void {
		$result = $this->service->canRevertToDraft( 1, Document::STATUS_DRAFT );
		$this->assertFalse( $result );
	}

	/**
	 * Test cannot revert cancelled document.
	 */
	public function test_can_revert_to_draft_returns_false_for_cancelled_status(): void {
		$result = $this->service->canRevertToDraft( 1, Document::STATUS_CANCELLED );
		$this->assertFalse( $result );
	}

	/**
	 * Test zero user ID cannot revert issued document.
	 */
	public function test_can_revert_to_draft_returns_false_for_zero_user_id(): void {
		$result = $this->service->canRevertToDraft( 0, Document::STATUS_ISSUED );
		$this->assertFalse( $result );
	}

	/**
	 * Test negative user ID cannot revert issued document.
	 */
	public function test_can_revert_to_draft_returns_false_for_negative_user_id(): void {
		$result = $this->service->canRevertToDraft( -1, Document::STATUS_ISSUED );
		$this->assertFalse( $result );
	}

	/**
	 * Test regular user cannot revert issued document when list is empty.
	 */
	public function test_can_revert_to_draft_returns_false_for_user_not_in_list(): void {
		if ( defined( 'IHUMBAK_SUPER_ADMIN_IDS' ) ) {
			$this->markTestSkipped( 'Constant already defined.' );
		}

		$result = $this->service->canRevertToDraft( 999, Document::STATUS_ISSUED );
		$this->assertFalse( $result );
	}

	/**
	 * Test regular user cannot revert sent document when list is empty.
	 */
	public function test_can_revert_to_draft_returns_false_for_sent_status_when_not_super_admin(): void {
		if ( defined( 'IHUMBAK_SUPER_ADMIN_IDS' ) ) {
			$this->markTestSkipped( 'Constant already defined.' );
		}

		$result = $this->service->canRevertToDraft( 999, Document::STATUS_SENT );
		$this->assertFalse( $result );
	}

	/**
	 * Test regular user cannot revert paid document when list is empty.
	 */
	public function test_can_revert_to_draft_returns_false_for_paid_status_when_not_super_admin(): void {
		if ( defined( 'IHUMBAK_SUPER_ADMIN_IDS' ) ) {
			$this->markTestSkipped( 'Constant already defined.' );
		}

		$result = $this->service->canRevertToDraft( 999, Document::STATUS_PAID );
		$this->assertFalse( $result );
	}

	// ==========================================================================
	// getRevertableStatuses() tests
	// ==========================================================================

	/**
	 * Test getRevertableStatuses returns expected statuses.
	 */
	public function test_get_revertable_statuses_returns_correct_list(): void {
		$statuses = SuperAdminService::getRevertableStatuses();

		$this->assertIsArray( $statuses );
		$this->assertCount( 3, $statuses );
		$this->assertContains( Document::STATUS_ISSUED, $statuses );
		$this->assertContains( Document::STATUS_SENT, $statuses );
		$this->assertContains( Document::STATUS_PAID, $statuses );
		$this->assertNotContains( Document::STATUS_DRAFT, $statuses );
		$this->assertNotContains( Document::STATUS_CANCELLED, $statuses );
	}

	// ==========================================================================
	// Constant value tests - these test parsing logic
	// ==========================================================================

	/**
	 * Test config constant name is correct.
	 */
	public function test_config_constant_name_is_correct(): void {
		$this->assertEquals( 'IHUMBAK_SUPER_ADMIN_IDS', SuperAdminService::CONFIG_CONSTANT );
	}
}
