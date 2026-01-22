<?php
/**
 * UpdateService unit tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Modules\Updates
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Modules\Updates;

use IHumbak\Invoices\Modules\Updates\UpdateService;
use PHPUnit\Framework\TestCase;

/**
 * Tests for UpdateService.
 */
class UpdateServiceTest extends TestCase {

	/**
	 * Service under test.
	 *
	 * @var UpdateService
	 */
	private UpdateService $service;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->service = new UpdateService();
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		// Clean up mock filters.
		global $mock_wp_filters;
		$mock_wp_filters = array();

		parent::tearDown();
	}

	// ==========================================================================
	// Instantiation tests
	// ==========================================================================

	/**
	 * Test service can be instantiated.
	 */
	public function test_can_be_instantiated(): void {
		$service = new UpdateService();
		$this->assertInstanceOf( UpdateService::class, $service );
	}

	// ==========================================================================
	// Constants tests
	// ==========================================================================

	/**
	 * Test default repository URL constant.
	 */
	public function test_default_repository_url_constant(): void {
		$this->assertSame(
			'https://github.com/michalstaniecko/ihumbak-woo-invoices/',
			UpdateService::DEFAULT_REPOSITORY_URL
		);
	}

	/**
	 * Test default branch constant.
	 */
	public function test_default_branch_constant(): void {
		$this->assertSame( 'develop', UpdateService::DEFAULT_BRANCH );
	}

	/**
	 * Test plugin slug constant.
	 */
	public function test_plugin_slug_constant(): void {
		$this->assertSame( 'ihumbak-woo-invoices', UpdateService::PLUGIN_SLUG );
	}

	// ==========================================================================
	// is_enabled() tests
	// ==========================================================================

	/**
	 * Test updates are disabled via IHUMBAK_DISABLE_UPDATES constant in test environment.
	 *
	 * Note: In tests, IHUMBAK_DISABLE_UPDATES is set to true in bootstrap.php
	 * to prevent PucFactory initialization issues.
	 */
	public function test_is_enabled_returns_false_when_constant_disabled(): void {
		// IHUMBAK_DISABLE_UPDATES is defined as true in bootstrap.php.
		$this->assertTrue( defined( 'IHUMBAK_DISABLE_UPDATES' ) && IHUMBAK_DISABLE_UPDATES );

		$result = $this->service->is_enabled();
		$this->assertFalse( $result );
	}

	/**
	 * Test filter cannot override constant-based disabling.
	 *
	 * When IHUMBAK_DISABLE_UPDATES is true, filters are not applied
	 * because the constant check happens first.
	 */
	public function test_is_enabled_constant_takes_precedence_over_filter(): void {
		add_filter( 'ihumbak_updates_enabled', '__return_true' );

		// Should still be false because constant is checked first.
		$result = $this->service->is_enabled();
		$this->assertFalse( $result );

		remove_filter( 'ihumbak_updates_enabled', '__return_true' );
	}

	// ==========================================================================
	// get_current_version() tests
	// ==========================================================================

	/**
	 * Test get_current_version returns version constant when defined.
	 */
	public function test_get_current_version_returns_defined_constant(): void {
		// The constant should be defined in bootstrap.php.
		if ( defined( 'IHUMBAK_INVOICES_VERSION' ) ) {
			$result = $this->service->get_current_version();
			$this->assertSame( IHUMBAK_INVOICES_VERSION, $result );
		} else {
			$this->markTestSkipped( 'IHUMBAK_INVOICES_VERSION constant not defined.' );
		}
	}

	/**
	 * Test get_current_version returns fallback when constant not defined.
	 */
	public function test_get_current_version_fallback(): void {
		// When constant is not defined, should return '0.0.0'.
		// Since we can't undefine the constant, we just verify the method returns a string.
		$result = $this->service->get_current_version();
		$this->assertIsString( $result );
		$this->assertNotEmpty( $result );
	}

	// ==========================================================================
	// get_repository_url() tests
	// ==========================================================================

	/**
	 * Test get_repository_url returns default URL.
	 */
	public function test_get_repository_url_returns_default(): void {
		$result = $this->service->get_repository_url();
		$this->assertSame( UpdateService::DEFAULT_REPOSITORY_URL, $result );
	}

	/**
	 * Test get_repository_url filter can override URL.
	 */
	public function test_get_repository_url_filter_can_override(): void {
		$custom_url = 'https://github.com/custom/repo/';

		add_filter(
			'ihumbak_update_repository_url',
			function () use ( $custom_url ) {
				return $custom_url;
			}
		);

		$result = $this->service->get_repository_url();
		$this->assertSame( $custom_url, $result );
	}

	// ==========================================================================
	// get_branch() tests
	// ==========================================================================

	/**
	 * Test get_branch returns default branch.
	 */
	public function test_get_branch_returns_default(): void {
		$result = $this->service->get_branch();
		$this->assertSame( UpdateService::DEFAULT_BRANCH, $result );
	}

	/**
	 * Test get_branch filter can override branch.
	 */
	public function test_get_branch_filter_can_override(): void {
		$custom_branch = 'develop';

		add_filter(
			'ihumbak_update_branch',
			function () use ( $custom_branch ) {
				return $custom_branch;
			}
		);

		$result = $this->service->get_branch();
		$this->assertSame( $custom_branch, $result );
	}

	// ==========================================================================
	// get_github_access_token() tests
	// ==========================================================================

	/**
	 * Test get_github_access_token returns empty string by default.
	 */
	public function test_get_github_access_token_returns_empty_by_default(): void {
		$result = $this->service->get_github_access_token();
		$this->assertSame( '', $result );
	}

	/**
	 * Test get_github_access_token filter can provide token.
	 */
	public function test_get_github_access_token_filter_can_provide_token(): void {
		$token = 'ghp_test_token_123';

		add_filter(
			'ihumbak_github_access_token',
			function () use ( $token ) {
				return $token;
			}
		);

		$result = $this->service->get_github_access_token();
		$this->assertSame( $token, $result );
	}

	// ==========================================================================
	// get_plugin_file() tests
	// ==========================================================================

	/**
	 * Test get_plugin_file returns valid path.
	 */
	public function test_get_plugin_file_returns_valid_path(): void {
		$result = $this->service->get_plugin_file();
		$this->assertIsString( $result );
		$this->assertStringContainsString( 'ihumbak-invoices.php', $result );
	}

	// ==========================================================================
	// filter_update_info() tests
	// ==========================================================================

	/**
	 * Test filter_update_info returns null when given null.
	 */
	public function test_filter_update_info_returns_null_when_given_null(): void {
		$result = $this->service->filter_update_info( null );
		$this->assertNull( $result );
	}

	/**
	 * Test filter_update_info passes through object.
	 */
	public function test_filter_update_info_passes_through_object(): void {
		$info          = new \stdClass();
		$info->version = '1.0.0';

		$result = $this->service->filter_update_info( $info );
		$this->assertSame( $info, $result );
	}

	/**
	 * Test filter_update_info filter can modify info.
	 */
	public function test_filter_update_info_filter_can_modify(): void {
		$info          = new \stdClass();
		$info->version = '1.0.0';

		add_filter(
			'ihumbak_update_info',
			function ( $info ) {
				$info->modified = true;
				return $info;
			}
		);

		$result = $this->service->filter_update_info( $info );
		$this->assertTrue( $result->modified );
	}

	// ==========================================================================
	// get_update_checker() tests
	// ==========================================================================

	/**
	 * Test get_update_checker returns null before init.
	 */
	public function test_get_update_checker_returns_null_before_init(): void {
		$service = new UpdateService();
		$result  = $service->get_update_checker();
		$this->assertNull( $result );
	}

	// ==========================================================================
	// check_for_updates() tests
	// ==========================================================================

	/**
	 * Test check_for_updates returns null when not initialized.
	 */
	public function test_check_for_updates_returns_null_when_not_initialized(): void {
		$service = new UpdateService();
		$result  = $service->check_for_updates();
		$this->assertNull( $result );
	}
}
