<?php
/**
 * SuperAdminService parsing tests with various constant formats.
 *
 * @package IHumbak\Invoices\Tests\Unit\Modules\Invoice
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Modules\Invoice;

use IHumbak\Invoices\Modules\Invoice\SuperAdminService;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SuperAdminService ID parsing with various formats.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SuperAdminServiceParsingTest extends TestCase {

	/**
	 * Test parsing single ID.
	 */
	public function test_parses_single_id(): void {
		define( 'IHUMBAK_SUPER_ADMIN_IDS', '42' );

		$service = new SuperAdminService();
		$result  = $service->getSuperAdminIds();

		$this->assertCount( 1, $result );
		$this->assertContains( 42, $result );
	}

	/**
	 * Test parsing IDs with whitespace.
	 */
	public function test_trims_whitespace(): void {
		define( 'IHUMBAK_SUPER_ADMIN_IDS', ' 1 , 5 , 12 ' );

		$service = new SuperAdminService();
		$result  = $service->getSuperAdminIds();

		$this->assertCount( 3, $result );
		$this->assertContains( 1, $result );
		$this->assertContains( 5, $result );
		$this->assertContains( 12, $result );
	}

	/**
	 * Test filtering invalid IDs (non-numeric).
	 */
	public function test_filters_non_numeric_ids(): void {
		define( 'IHUMBAK_SUPER_ADMIN_IDS', '1,abc,5,xyz,12' );

		$service = new SuperAdminService();
		$result  = $service->getSuperAdminIds();

		$this->assertCount( 3, $result );
		$this->assertContains( 1, $result );
		$this->assertContains( 5, $result );
		$this->assertContains( 12, $result );
	}

	/**
	 * Test filtering negative IDs.
	 */
	public function test_filters_negative_ids(): void {
		define( 'IHUMBAK_SUPER_ADMIN_IDS', '1,-3,5,-7,12' );

		$service = new SuperAdminService();
		$result  = $service->getSuperAdminIds();

		$this->assertCount( 3, $result );
		$this->assertContains( 1, $result );
		$this->assertContains( 5, $result );
		$this->assertContains( 12, $result );
	}

	/**
	 * Test filtering zero.
	 */
	public function test_filters_zero(): void {
		define( 'IHUMBAK_SUPER_ADMIN_IDS', '1,0,5' );

		$service = new SuperAdminService();
		$result  = $service->getSuperAdminIds();

		$this->assertCount( 2, $result );
		$this->assertContains( 1, $result );
		$this->assertContains( 5, $result );
		$this->assertNotContains( 0, $result );
	}

	/**
	 * Test empty string returns empty array.
	 */
	public function test_empty_string_returns_empty(): void {
		define( 'IHUMBAK_SUPER_ADMIN_IDS', '' );

		$service = new SuperAdminService();
		$result  = $service->getSuperAdminIds();

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test whitespace only returns empty array.
	 */
	public function test_whitespace_only_returns_empty(): void {
		define( 'IHUMBAK_SUPER_ADMIN_IDS', '   ' );

		$service = new SuperAdminService();
		$result  = $service->getSuperAdminIds();

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test float IDs are truncated to integers.
	 */
	public function test_float_ids_are_converted_to_integers(): void {
		define( 'IHUMBAK_SUPER_ADMIN_IDS', '1.5,5.9,12.1' );

		$service = new SuperAdminService();
		$result  = $service->getSuperAdminIds();

		// Note: is_numeric returns true for floats, so they pass the filter
		// and intval converts them to integers.
		$this->assertContains( 1, $result );
		$this->assertContains( 5, $result );
		$this->assertContains( 12, $result );
	}
}
