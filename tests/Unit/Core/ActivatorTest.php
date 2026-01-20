<?php
/**
 * Activator unit tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Core
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Core;

use IHumbak\Invoices\Core\Activator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Test case for Activator class.
 */
class ActivatorTest extends TestCase {

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Clear mock options before each test.
		global $mock_wp_options;
		$mock_wp_options = array();
	}

	/**
	 * Test migrate_settings migrates nip_meta_key from automation to display.
	 *
	 * @return void
	 */
	public function test_migrate_settings_moves_nip_meta_key_from_automation_to_display(): void {
		// Setup: old settings with automation.nip_meta_key.
		update_option(
			'ihumbak_invoices_settings',
			array(
				'seller'     => array(
					'name' => 'Test Company',
				),
				'automation' => array(
					'auto_generate_invoice' => true,
					'nip_meta_key'          => '_custom_vat_number',
				),
				'display'    => array(
					'show_order_column' => true,
				),
			)
		);

		// Act: invoke private migrate_settings method via reflection.
		$this->invoke_migrate_settings();

		// Assert: nip_meta_key is migrated to display section.
		$settings = get_option( 'ihumbak_invoices_settings' );

		$this->assertEquals( '_custom_vat_number', $settings['display']['nip_meta_key'] );
		$this->assertArrayNotHasKey( 'automation', $settings );
	}

	/**
	 * Test migrate_settings preserves existing display.nip_meta_key if not default.
	 *
	 * @return void
	 */
	public function test_migrate_settings_preserves_non_default_display_nip_meta_key(): void {
		// Setup: settings with both automation and display nip_meta_key (non-default).
		update_option(
			'ihumbak_invoices_settings',
			array(
				'automation' => array(
					'nip_meta_key' => '_old_automation_key',
				),
				'display'    => array(
					'show_order_column' => true,
					'nip_meta_key'      => '_already_configured_key',
				),
			)
		);

		// Act: invoke private migrate_settings method.
		$this->invoke_migrate_settings();

		// Assert: existing non-default display.nip_meta_key is preserved.
		$settings = get_option( 'ihumbak_invoices_settings' );

		$this->assertEquals( '_already_configured_key', $settings['display']['nip_meta_key'] );
		$this->assertArrayNotHasKey( 'automation', $settings );
	}

	/**
	 * Test migrate_settings migrates when display.nip_meta_key is default value.
	 *
	 * @return void
	 */
	public function test_migrate_settings_overwrites_default_display_nip_meta_key(): void {
		// Setup: settings with automation.nip_meta_key and default display.nip_meta_key.
		update_option(
			'ihumbak_invoices_settings',
			array(
				'automation' => array(
					'nip_meta_key' => '_custom_from_automation',
				),
				'display'    => array(
					'show_order_column' => true,
					'nip_meta_key'      => '_billing_nip', // Default value.
				),
			)
		);

		// Act: invoke private migrate_settings method.
		$this->invoke_migrate_settings();

		// Assert: default value is overwritten with automation value.
		$settings = get_option( 'ihumbak_invoices_settings' );

		$this->assertEquals( '_custom_from_automation', $settings['display']['nip_meta_key'] );
	}

	/**
	 * Test migrate_settings removes automation section completely.
	 *
	 * @return void
	 */
	public function test_migrate_settings_removes_automation_section(): void {
		// Setup: settings with automation section (but no nip_meta_key).
		update_option(
			'ihumbak_invoices_settings',
			array(
				'seller'     => array(
					'name' => 'Test Company',
				),
				'automation' => array(
					'auto_generate_invoice' => true,
					'trigger_status'        => 'completed',
				),
			)
		);

		// Act: invoke private migrate_settings method.
		$this->invoke_migrate_settings();

		// Assert: automation section is removed.
		$settings = get_option( 'ihumbak_invoices_settings' );

		$this->assertArrayNotHasKey( 'automation', $settings );
		$this->assertEquals( 'Test Company', $settings['seller']['name'] );
	}

	/**
	 * Test migrate_settings does nothing when no settings exist.
	 *
	 * @return void
	 */
	public function test_migrate_settings_handles_no_settings(): void {
		// Setup: no settings.
		delete_option( 'ihumbak_invoices_settings' );

		// Act: invoke private migrate_settings method - should not throw.
		$this->invoke_migrate_settings();

		// Assert: no settings created.
		$this->assertFalse( get_option( 'ihumbak_invoices_settings' ) );
	}

	/**
	 * Test migrate_settings does nothing when settings is not array.
	 *
	 * @return void
	 */
	public function test_migrate_settings_handles_non_array_settings(): void {
		// Setup: invalid settings value.
		update_option( 'ihumbak_invoices_settings', 'invalid' );

		// Act: invoke private migrate_settings method - should not throw.
		$this->invoke_migrate_settings();

		// Assert: settings unchanged.
		$this->assertEquals( 'invalid', get_option( 'ihumbak_invoices_settings' ) );
	}

	/**
	 * Invoke the private migrate_settings method using reflection.
	 *
	 * @return void
	 */
	private function invoke_migrate_settings(): void {
		$reflection = new ReflectionClass( Activator::class );
		$method     = $reflection->getMethod( 'migrate_settings' );
		$method->setAccessible( true );
		$method->invoke( null );
	}
}
