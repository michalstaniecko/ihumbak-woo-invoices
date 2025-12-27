<?php
/**
 * Plugin unit tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Core
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Core;

use IHumbak\Invoices\Core\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * Test case for Plugin class.
 */
class PluginTest extends TestCase {

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
	 * Test sanitize_settings preserves existing settings from other tabs.
	 *
	 * When saving settings from one tab (e.g., seller), settings from other tabs
	 * (e.g., numbering, pdf) should not be overwritten.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_preserves_other_tabs(): void {
		// Setup: existing settings with data in multiple tabs.
		update_option(
			'ihumbak_invoices_settings',
			array(
				'seller'    => array(
					'name'    => 'Existing Company',
					'details' => 'Existing Details',
				),
				'numbering' => array(
					'invoice_pattern' => 'FV/{YYYY}/{MM}/{NNNN}',
					'reset_monthly'   => true,
				),
				'pdf'       => array(
					'template'    => 'custom',
					'logo_id'     => 123,
					'footer_text' => 'Footer',
				),
			)
		);

		$plugin = Plugin::get_instance();

		// Act: submit only seller tab data.
		$input = array(
			'seller' => array(
				'name'    => 'New Company Name',
				'details' => 'New Details',
			),
		);

		$result = $plugin->sanitize_settings( $input );

		// Assert: seller data is updated.
		$this->assertEquals( 'New Company Name', $result['seller']['name'] );
		$this->assertEquals( 'New Details', $result['seller']['details'] );

		// Assert: other tabs are preserved.
		$this->assertEquals( 'FV/{YYYY}/{MM}/{NNNN}', $result['numbering']['invoice_pattern'] );
		$this->assertTrue( $result['numbering']['reset_monthly'] );
		$this->assertEquals( 'custom', $result['pdf']['template'] );
		$this->assertEquals( 123, $result['pdf']['logo_id'] );
		$this->assertEquals( 'Footer', $result['pdf']['footer_text'] );
	}

	/**
	 * Test sanitize_settings preserves seller when saving numbering tab.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_preserves_seller_when_saving_numbering(): void {
		// Setup: existing seller settings.
		update_option(
			'ihumbak_invoices_settings',
			array(
				'seller' => array(
					'name'    => 'My Company',
					'details' => 'NIP: 123456789',
				),
			)
		);

		$plugin = Plugin::get_instance();

		// Act: submit only numbering tab data.
		$input = array(
			'numbering' => array(
				'invoice_pattern'     => 'INV/{YYYY}/{NNNN}',
				'receipt_pattern'     => 'REC/{YYYY}/{NNNN}',
				'credit_note_pattern' => 'CN/{YYYY}/{NNNN}',
				'correction_pattern'  => 'COR/{YYYY}/{NNNN}',
				'reset_monthly'       => false,
			),
		);

		$result = $plugin->sanitize_settings( $input );

		// Assert: seller data is preserved.
		$this->assertEquals( 'My Company', $result['seller']['name'] );
		$this->assertEquals( 'NIP: 123456789', $result['seller']['details'] );

		// Assert: numbering data is updated.
		$this->assertEquals( 'INV/{YYYY}/{NNNN}', $result['numbering']['invoice_pattern'] );
		$this->assertFalse( $result['numbering']['reset_monthly'] );
	}

	/**
	 * Test sanitize_settings handles empty existing options.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_handles_empty_existing_options(): void {
		// No existing options set.
		$plugin = Plugin::get_instance();

		$input = array(
			'seller' => array(
				'name'    => 'First Company',
				'details' => 'First Details',
			),
		);

		$result = $plugin->sanitize_settings( $input );

		// Assert: seller data is set correctly.
		$this->assertEquals( 'First Company', $result['seller']['name'] );
		$this->assertEquals( 'First Details', $result['seller']['details'] );

		// Assert: no other keys exist (nothing to preserve).
		$this->assertArrayHasKey( 'seller', $result );
		$this->assertArrayNotHasKey( 'numbering', $result );
	}

	/**
	 * Test sanitize_settings handles non-array existing options.
	 *
	 * Edge case: if the option exists but is not an array.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_handles_non_array_existing_options(): void {
		// Set a non-array value (corrupted data).
		update_option( 'ihumbak_invoices_settings', 'invalid_string' );

		$plugin = Plugin::get_instance();

		$input = array(
			'seller' => array(
				'name'    => 'Company',
				'details' => 'Details',
			),
		);

		$result = $plugin->sanitize_settings( $input );

		// Assert: should work correctly, not error out.
		$this->assertEquals( 'Company', $result['seller']['name'] );
	}

	/**
	 * Test sanitize_settings preserves all five tabs independently.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_preserves_all_five_tabs(): void {
		// Setup: all five tabs have data.
		update_option(
			'ihumbak_invoices_settings',
			array(
				'seller'     => array(
					'name'    => 'Seller Name',
					'details' => 'Seller Details',
				),
				'numbering'  => array(
					'invoice_pattern' => 'FV/{YYYY}/{NNNN}',
					'reset_monthly'   => true,
				),
				'pdf'        => array(
					'template' => 'default',
				),
				'automation' => array(
					'auto_generate_invoice' => true,
					'trigger_status'        => 'completed',
				),
				'display'    => array(
					'show_order_column' => true,
				),
			)
		);

		$plugin = Plugin::get_instance();

		// Act: update only display tab.
		$input = array(
			'display' => array(
				'show_order_column' => false,
			),
		);

		$result = $plugin->sanitize_settings( $input );

		// Assert: display is updated.
		$this->assertFalse( $result['display']['show_order_column'] );

		// Assert: all other tabs are preserved.
		$this->assertEquals( 'Seller Name', $result['seller']['name'] );
		$this->assertEquals( 'FV/{YYYY}/{NNNN}', $result['numbering']['invoice_pattern'] );
		$this->assertEquals( 'default', $result['pdf']['template'] );
		$this->assertTrue( $result['automation']['auto_generate_invoice'] );
	}
}
