<?php
/**
 * TemplateLoader tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Modules\PDF
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Modules\PDF;

use IHumbak\Invoices\Modules\PDF\TemplateLoader;
use PHPUnit\Framework\TestCase;

/**
 * Test TemplateLoader class.
 */
class TemplateLoaderTest extends TestCase {

	/**
	 * Template loader instance.
	 *
	 * @var TemplateLoader
	 */
	private TemplateLoader $loader;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->loader = new TemplateLoader();
	}

	/**
	 * Test getTemplatePaths returns array.
	 */
	public function test_get_template_paths_returns_array(): void {
		$paths = $this->loader->getTemplatePaths();

		$this->assertIsArray( $paths );
		$this->assertNotEmpty( $paths );
	}

	/**
	 * Test getTemplatePaths includes plugin path.
	 */
	public function test_get_template_paths_includes_plugin_path(): void {
		$paths = $this->loader->getTemplatePaths();

		$plugin_path_found = false;
		foreach ( $paths as $path ) {
			if ( str_contains( $path, 'templates/pdf' ) ) {
				$plugin_path_found = true;
				break;
			}
		}

		$this->assertTrue( $plugin_path_found, 'Plugin templates/pdf path should be included' );
	}

	/**
	 * Test locate returns null for non-existent template.
	 */
	public function test_locate_returns_null_for_nonexistent_template(): void {
		$result = $this->loader->locate( 'nonexistent', 'template' );

		$this->assertNull( $result );
	}

	/**
	 * Test locate finds existing template.
	 */
	public function test_locate_finds_existing_template(): void {
		$result = $this->loader->locate( 'default', 'invoice' );

		$this->assertNotNull( $result );
		$this->assertStringContainsString( 'invoice.php', $result );
	}

	/**
	 * Test locateStylesheet returns null for non-existent stylesheet.
	 */
	public function test_locate_stylesheet_returns_null_for_nonexistent(): void {
		$result = $this->loader->locateStylesheet( 'nonexistent' );

		$this->assertNull( $result );
	}

	/**
	 * Test locateStylesheet finds existing stylesheet.
	 */
	public function test_locate_stylesheet_finds_existing(): void {
		$result = $this->loader->locateStylesheet( 'default' );

		$this->assertNotNull( $result );
		$this->assertStringContainsString( 'styles.css', $result );
	}

	/**
	 * Test loadStylesheet returns string.
	 */
	public function test_load_stylesheet_returns_string(): void {
		$result = $this->loader->loadStylesheet( 'default' );

		$this->assertIsString( $result );
		$this->assertNotEmpty( $result );
	}

	/**
	 * Test loadStylesheet returns empty string for non-existent.
	 */
	public function test_load_stylesheet_returns_empty_for_nonexistent(): void {
		$result = $this->loader->loadStylesheet( 'nonexistent' );

		$this->assertIsString( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test templateExists returns true for existing template.
	 */
	public function test_template_exists_returns_true_for_existing(): void {
		$result = $this->loader->templateExists( 'default', 'invoice' );

		$this->assertTrue( $result );
	}

	/**
	 * Test templateExists returns false for non-existent template.
	 */
	public function test_template_exists_returns_false_for_nonexistent(): void {
		$result = $this->loader->templateExists( 'nonexistent', 'template' );

		$this->assertFalse( $result );
	}

	/**
	 * Test getTemplateSource returns plugin for plugin templates.
	 */
	public function test_get_template_source_returns_plugin_for_plugin_templates(): void {
		$result = $this->loader->getTemplateSource( 'default', 'invoice' );

		$this->assertSame( 'plugin', $result );
	}

	/**
	 * Test getTemplateSource returns unknown for non-existent.
	 */
	public function test_get_template_source_returns_unknown_for_nonexistent(): void {
		$result = $this->loader->getTemplateSource( 'nonexistent', 'template' );

		$this->assertSame( 'unknown', $result );
	}

	/**
	 * Test clearCache clears the internal cache.
	 */
	public function test_clear_cache_clears_internal_cache(): void {
		// First locate a template to populate cache.
		$this->loader->locate( 'default', 'invoice' );

		// Clear cache.
		$this->loader->clearCache();

		// This should work fine after cache clear.
		$result = $this->loader->locate( 'default', 'invoice' );
		$this->assertNotNull( $result );
	}

	/**
	 * Test render throws exception for non-existent template.
	 */
	public function test_render_throws_exception_for_nonexistent_template(): void {
		$this->expectException( \RuntimeException::class );

		$this->loader->render( 'nonexistent', 'template', array() );
	}
}
