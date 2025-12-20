<?php
/**
 * TemplateRegistry tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Modules\PDF
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Modules\PDF;

use IHumbak\Invoices\Modules\PDF\TemplateRegistry;
use IHumbak\Invoices\Modules\PDF\TemplateLoader;
use PHPUnit\Framework\TestCase;

/**
 * Test TemplateRegistry class.
 */
class TemplateRegistryTest extends TestCase {

	/**
	 * Template registry instance.
	 *
	 * @var TemplateRegistry
	 */
	private TemplateRegistry $registry;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->registry = new TemplateRegistry( new TemplateLoader() );
	}

	/**
	 * Test getAvailableTemplateSets returns array.
	 */
	public function test_get_available_template_sets_returns_array(): void {
		$sets = $this->registry->getAvailableTemplateSets( false );

		$this->assertIsArray( $sets );
	}

	/**
	 * Test getAvailableTemplateSets includes default template.
	 */
	public function test_get_available_template_sets_includes_default(): void {
		$sets = $this->registry->getAvailableTemplateSets( false );

		$this->assertArrayHasKey( 'default', $sets );
	}

	/**
	 * Test default template set has correct structure.
	 */
	public function test_default_template_set_has_correct_structure(): void {
		$sets = $this->registry->getAvailableTemplateSets( false );

		$this->assertArrayHasKey( 'default', $sets );

		$default = $sets['default'];
		$this->assertArrayHasKey( 'name', $default );
		$this->assertArrayHasKey( 'path', $default );
		$this->assertArrayHasKey( 'source', $default );
		$this->assertArrayHasKey( 'has_invoice', $default );
		$this->assertArrayHasKey( 'has_receipt', $default );
		$this->assertArrayHasKey( 'has_styles', $default );
	}

	/**
	 * Test default template set has all required templates.
	 */
	public function test_default_template_set_has_all_required_templates(): void {
		$sets    = $this->registry->getAvailableTemplateSets( false );
		$default = $sets['default'];

		$this->assertTrue( $default['has_invoice'] );
		$this->assertTrue( $default['has_receipt'] );
		$this->assertTrue( $default['has_styles'] );
	}

	/**
	 * Test isValidTemplateSet returns true for default.
	 */
	public function test_is_valid_template_set_returns_true_for_default(): void {
		$result = $this->registry->isValidTemplateSet( 'default' );

		$this->assertTrue( $result );
	}

	/**
	 * Test isValidTemplateSet returns false for non-existent.
	 */
	public function test_is_valid_template_set_returns_false_for_nonexistent(): void {
		$result = $this->registry->isValidTemplateSet( 'nonexistent' );

		$this->assertFalse( $result );
	}

	/**
	 * Test isCompleteTemplateSet returns true for default.
	 */
	public function test_is_complete_template_set_returns_true_for_default(): void {
		$result = $this->registry->isCompleteTemplateSet( 'default' );

		$this->assertTrue( $result );
	}

	/**
	 * Test isCompleteTemplateSet returns false for non-existent.
	 */
	public function test_is_complete_template_set_returns_false_for_nonexistent(): void {
		$result = $this->registry->isCompleteTemplateSet( 'nonexistent' );

		$this->assertFalse( $result );
	}

	/**
	 * Test getTemplateSet returns array for existing set.
	 */
	public function test_get_template_set_returns_array_for_existing(): void {
		$result = $this->registry->getTemplateSet( 'default' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'name', $result );
	}

	/**
	 * Test getTemplateSet returns null for non-existent.
	 */
	public function test_get_template_set_returns_null_for_nonexistent(): void {
		$result = $this->registry->getTemplateSet( 'nonexistent' );

		$this->assertNull( $result );
	}

	/**
	 * Test getSelectOptions returns array.
	 */
	public function test_get_select_options_returns_array(): void {
		$result = $this->registry->getSelectOptions();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'default', $result );
	}

	/**
	 * Test getSelectOptions returns formatted labels.
	 */
	public function test_get_select_options_returns_formatted_labels(): void {
		$result = $this->registry->getSelectOptions();

		// Default should have a formatted label.
		$this->assertIsString( $result['default'] );
		$this->assertNotEmpty( $result['default'] );
	}

	/**
	 * Test getDefaultTemplateSet returns default.
	 */
	public function test_get_default_template_set_returns_default(): void {
		$result = $this->registry->getDefaultTemplateSet();

		$this->assertSame( 'default', $result );
	}

	/**
	 * Test clearCache does not throw exception.
	 */
	public function test_clear_cache_does_not_throw(): void {
		$this->registry->clearCache();

		// If we get here without exception, the test passes.
		$this->assertTrue( true );
	}
}
