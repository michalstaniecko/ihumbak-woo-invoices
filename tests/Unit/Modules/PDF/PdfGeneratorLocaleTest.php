<?php
/**
 * PdfGenerator locale switching tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Modules\PDF
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Modules\PDF;

use IHumbak\Invoices\Modules\PDF\PdfGenerator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Test PdfGenerator locale switching functionality.
 */
class PdfGeneratorLocaleTest extends TestCase {

	/**
	 * PdfGenerator instance.
	 *
	 * @var PdfGenerator
	 */
	private PdfGenerator $generator;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->generator = new PdfGenerator();

		// Reset mock state.
		global $mock_wp_options, $mock_current_locale, $mock_locale_stack;
		$mock_wp_options     = array();
		$mock_current_locale = 'en_US';
		$mock_locale_stack   = array();
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		// Reset mock state.
		global $mock_wp_options, $mock_current_locale, $mock_locale_stack;
		$mock_wp_options     = array();
		$mock_current_locale = 'en_US';
		$mock_locale_stack   = array();

		parent::tearDown();
	}

	/**
	 * Get a private method for testing.
	 *
	 * @param string $method_name Method name.
	 * @return ReflectionMethod
	 */
	private function getPrivateMethod( string $method_name ): ReflectionMethod {
		$reflection = new ReflectionClass( PdfGenerator::class );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );

		return $method;
	}

	/**
	 * Set a private property value.
	 *
	 * @param string $property_name Property name.
	 * @param mixed  $value         Property value.
	 */
	private function setPrivateProperty( string $property_name, $value ): void {
		$reflection = new ReflectionClass( PdfGenerator::class );
		$property   = $reflection->getProperty( $property_name );
		$property->setAccessible( true );
		$property->setValue( $this->generator, $value );
	}

	/**
	 * Get a private property value.
	 *
	 * @param string $property_name Property name.
	 * @return mixed
	 */
	private function getPrivateProperty( string $property_name ) {
		$reflection = new ReflectionClass( PdfGenerator::class );
		$property   = $reflection->getProperty( $property_name );
		$property->setAccessible( true );

		return $property->getValue( $this->generator );
	}

	/**
	 * Test getSiteLocale returns en_US when WPLANG is empty.
	 */
	public function test_get_site_locale_returns_en_us_when_wplang_empty(): void {
		global $mock_wp_options;
		$mock_wp_options['WPLANG'] = '';

		$method = $this->getPrivateMethod( 'getSiteLocale' );
		$result = $method->invoke( $this->generator );

		$this->assertSame( 'en_US', $result );
	}

	/**
	 * Test getSiteLocale returns en_US when WPLANG is not set.
	 */
	public function test_get_site_locale_returns_en_us_when_wplang_not_set(): void {
		// WPLANG not in options.
		$method = $this->getPrivateMethod( 'getSiteLocale' );
		$result = $method->invoke( $this->generator );

		$this->assertSame( 'en_US', $result );
	}

	/**
	 * Test getSiteLocale returns WPLANG value when set.
	 */
	public function test_get_site_locale_returns_wplang_value(): void {
		global $mock_wp_options;
		$mock_wp_options['WPLANG'] = 'nb_NO';

		$method = $this->getPrivateMethod( 'getSiteLocale' );
		$result = $method->invoke( $this->generator );

		$this->assertSame( 'nb_NO', $result );
	}

	/**
	 * Test getSiteLocale returns various locales correctly.
	 *
	 * @dataProvider localeProvider
	 * @param string $wplang_value WPLANG option value.
	 * @param string $expected     Expected locale.
	 */
	public function test_get_site_locale_with_various_values( string $wplang_value, string $expected ): void {
		global $mock_wp_options;
		$mock_wp_options['WPLANG'] = $wplang_value;

		$method = $this->getPrivateMethod( 'getSiteLocale' );
		$result = $method->invoke( $this->generator );

		$this->assertSame( $expected, $result );
	}

	/**
	 * Data provider for locale tests.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function localeProvider(): array {
		return array(
			'empty returns en_US' => array( '', 'en_US' ),
			'polish locale'       => array( 'pl_PL', 'pl_PL' ),
			'norwegian locale'    => array( 'nb_NO', 'nb_NO' ),
			'german locale'       => array( 'de_DE', 'de_DE' ),
			'french locale'       => array( 'fr_FR', 'fr_FR' ),
			'spanish locale'      => array( 'es_ES', 'es_ES' ),
		);
	}

	/**
	 * Test switchToSiteLocale returns false when locales match.
	 */
	public function test_switch_to_site_locale_returns_false_when_locales_match(): void {
		global $mock_wp_options, $mock_current_locale;
		$mock_wp_options['WPLANG'] = 'nb_NO';
		$mock_current_locale       = 'nb_NO';

		$method = $this->getPrivateMethod( 'switchToSiteLocale' );
		$result = $method->invoke( $this->generator );

		$this->assertFalse( $result );
	}

	/**
	 * Test switchToSiteLocale returns true when locales differ.
	 */
	public function test_switch_to_site_locale_returns_true_when_locales_differ(): void {
		global $mock_wp_options, $mock_current_locale;
		$mock_wp_options['WPLANG'] = 'nb_NO';
		$mock_current_locale       = 'en_US';

		$method = $this->getPrivateMethod( 'switchToSiteLocale' );
		$result = $method->invoke( $this->generator );

		$this->assertTrue( $result );
	}

	/**
	 * Test switchToSiteLocale changes current locale.
	 */
	public function test_switch_to_site_locale_changes_current_locale(): void {
		global $mock_wp_options, $mock_current_locale;
		$mock_wp_options['WPLANG'] = 'nb_NO';
		$mock_current_locale       = 'en_US';

		$method = $this->getPrivateMethod( 'switchToSiteLocale' );
		$method->invoke( $this->generator );

		$this->assertSame( 'nb_NO', $mock_current_locale );
	}

	/**
	 * Test switchToSiteLocale stores original locale.
	 */
	public function test_switch_to_site_locale_stores_original_locale(): void {
		global $mock_wp_options, $mock_current_locale;
		$mock_wp_options['WPLANG'] = 'nb_NO';
		$mock_current_locale       = 'en_US';

		$method = $this->getPrivateMethod( 'switchToSiteLocale' );
		$method->invoke( $this->generator );

		$original_locale = $this->getPrivateProperty( 'original_locale' );
		$pdf_locale      = $this->getPrivateProperty( 'pdf_locale' );

		$this->assertSame( 'en_US', $original_locale );
		$this->assertSame( 'nb_NO', $pdf_locale );
	}

	/**
	 * Test restoreLocale restores previous locale.
	 */
	public function test_restore_locale_restores_previous(): void {
		global $mock_wp_options, $mock_current_locale;
		$mock_wp_options['WPLANG'] = 'nb_NO';
		$mock_current_locale       = 'en_US';

		// Switch locale.
		$switch_method = $this->getPrivateMethod( 'switchToSiteLocale' );
		$switch_method->invoke( $this->generator );

		$this->assertSame( 'nb_NO', $mock_current_locale );

		// Restore locale.
		$restore_method = $this->getPrivateMethod( 'restoreLocale' );
		$restore_method->invoke( $this->generator );

		$this->assertSame( 'en_US', $mock_current_locale );
	}

	/**
	 * Test restoreLocale clears locale state.
	 */
	public function test_restore_locale_clears_state(): void {
		// Set up initial state.
		$this->setPrivateProperty( 'original_locale', 'en_US' );
		$this->setPrivateProperty( 'pdf_locale', 'nb_NO' );

		$method = $this->getPrivateMethod( 'restoreLocale' );
		$method->invoke( $this->generator );

		$this->assertNull( $this->getPrivateProperty( 'original_locale' ) );
		$this->assertNull( $this->getPrivateProperty( 'pdf_locale' ) );
	}

	/**
	 * Test ihumbak_pdf_locale filter can override locale.
	 */
	public function test_pdf_locale_filter_can_override(): void {
		global $mock_wp_options, $mock_current_locale, $mock_wp_filters;

		$mock_wp_options['WPLANG'] = 'nb_NO';
		$mock_current_locale       = 'en_US';

		// Add filter to override locale.
		add_filter(
			'ihumbak_pdf_locale',
			function ( $locale ) {
				return 'de_DE'; // Override to German.
			}
		);

		$method = $this->getPrivateMethod( 'switchToSiteLocale' );
		$method->invoke( $this->generator );

		$pdf_locale = $this->getPrivateProperty( 'pdf_locale' );
		$this->assertSame( 'de_DE', $pdf_locale );

		// Clean up filter.
		$mock_wp_filters = array();
	}

	/**
	 * Test locale does not change when locales already match.
	 */
	public function test_locale_stack_unchanged_when_locales_match(): void {
		global $mock_wp_options, $mock_current_locale, $mock_locale_stack;
		$mock_wp_options['WPLANG'] = 'en_US';
		$mock_current_locale       = 'en_US';
		$mock_locale_stack         = array();

		$method = $this->getPrivateMethod( 'switchToSiteLocale' );
		$method->invoke( $this->generator );

		// Stack should remain empty since no switch occurred.
		$this->assertEmpty( $mock_locale_stack );
	}

	/**
	 * Test locale stack is populated when locales differ.
	 */
	public function test_locale_stack_populated_when_locales_differ(): void {
		global $mock_wp_options, $mock_current_locale, $mock_locale_stack;
		$mock_wp_options['WPLANG'] = 'nb_NO';
		$mock_current_locale       = 'en_US';
		$mock_locale_stack         = array();

		$method = $this->getPrivateMethod( 'switchToSiteLocale' );
		$method->invoke( $this->generator );

		// Stack should contain original locale.
		$this->assertContains( 'en_US', $mock_locale_stack );
	}
}
