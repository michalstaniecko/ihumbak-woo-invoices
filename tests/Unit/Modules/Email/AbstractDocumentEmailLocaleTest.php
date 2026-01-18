<?php
/**
 * AbstractDocumentEmail locale switching tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Modules\Email
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Modules\Email;

use IHumbak\Invoices\Modules\Email\AbstractDocumentEmail;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Concrete implementation of AbstractDocumentEmail for testing.
 */
class TestDocumentEmail extends AbstractDocumentEmail {

	/**
	 * Get email ID.
	 *
	 * @return string
	 */
	protected function get_email_id(): string {
		return 'test_document_email';
	}

	/**
	 * Get email title.
	 *
	 * @return string
	 */
	protected function get_email_title(): string {
		return 'Test Document Email';
	}

	/**
	 * Get email description.
	 *
	 * @return string
	 */
	protected function get_email_description(): string {
		return 'Test email for unit testing locale functionality.';
	}

	/**
	 * Get document type.
	 *
	 * @return string
	 */
	protected function get_document_type(): string {
		return 'invoice';
	}

	/**
	 * Get template name.
	 *
	 * @return string
	 */
	protected function get_template_name(): string {
		return 'test-document';
	}
}

/**
 * Test AbstractDocumentEmail locale switching functionality.
 */
class AbstractDocumentEmailLocaleTest extends TestCase {

	/**
	 * TestDocumentEmail instance.
	 *
	 * @var TestDocumentEmail
	 */
	private TestDocumentEmail $email;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->email = new TestDocumentEmail();

		// Reset mock state.
		global $mock_wp_options, $mock_current_locale, $mock_locale_stack, $mock_wp_filters;
		$mock_wp_options     = array();
		$mock_current_locale = 'en_US';
		$mock_locale_stack   = array();
		$mock_wp_filters     = array();
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		// Reset mock state.
		global $mock_wp_options, $mock_current_locale, $mock_locale_stack, $mock_wp_filters;
		$mock_wp_options     = array();
		$mock_current_locale = 'en_US';
		$mock_locale_stack   = array();
		$mock_wp_filters     = array();

		parent::tearDown();
	}

	/**
	 * Get a private method for testing.
	 *
	 * @param string $method_name Method name.
	 * @return ReflectionMethod
	 */
	private function getPrivateMethod( string $method_name ): ReflectionMethod {
		// Use AbstractDocumentEmail since that's where SiteLocaleTrait methods are used.
		$reflection = new ReflectionClass( AbstractDocumentEmail::class );
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
		// Use AbstractDocumentEmail since trait properties are defined there.
		$reflection = new ReflectionClass( AbstractDocumentEmail::class );
		$property   = $reflection->getProperty( $property_name );
		$property->setAccessible( true );
		$property->setValue( $this->email, $value );
	}

	/**
	 * Get a private property value.
	 *
	 * @param string $property_name Property name.
	 * @return mixed
	 */
	private function getPrivateProperty( string $property_name ) {
		// Use AbstractDocumentEmail since trait properties are defined there.
		$reflection = new ReflectionClass( AbstractDocumentEmail::class );
		$property   = $reflection->getProperty( $property_name );
		$property->setAccessible( true );

		return $property->getValue( $this->email );
	}

	/**
	 * Test switchToSiteLocale stores original locale.
	 */
	public function test_switch_to_site_locale_stores_original_locale(): void {
		global $mock_wp_options, $mock_current_locale;
		$mock_wp_options['WPLANG'] = 'nb_NO';
		$mock_current_locale       = 'en_US';

		$method = $this->getPrivateMethod( 'switchToSiteLocale' );
		$method->invoke( $this->email, 'ihumbak_email_locale' );

		$original_locale = $this->getPrivateProperty( 'original_locale' );

		$this->assertSame( 'en_US', $original_locale );
	}

	/**
	 * Test switchToSiteLocale sets target locale.
	 */
	public function test_switch_to_site_locale_sets_target_locale(): void {
		global $mock_wp_options, $mock_current_locale;
		$mock_wp_options['WPLANG'] = 'nb_NO';
		$mock_current_locale       = 'en_US';

		$method = $this->getPrivateMethod( 'switchToSiteLocale' );
		$method->invoke( $this->email, 'ihumbak_email_locale' );

		$target_locale = $this->getPrivateProperty( 'target_locale' );

		$this->assertSame( 'nb_NO', $target_locale );
	}

	/**
	 * Test restoreLocale restores previous locale and clears state.
	 */
	public function test_restore_locale_restores_previous_and_clears_state(): void {
		global $mock_wp_options, $mock_current_locale;
		$mock_wp_options['WPLANG'] = 'nb_NO';
		$mock_current_locale       = 'en_US';

		// Switch locale first.
		$switch_method = $this->getPrivateMethod( 'switchToSiteLocale' );
		$switch_method->invoke( $this->email );

		$this->assertSame( 'nb_NO', $mock_current_locale );

		// Restore locale.
		$restore_method = $this->getPrivateMethod( 'restoreLocale' );
		$restore_method->invoke( $this->email );

		// Check locale was restored.
		$this->assertSame( 'en_US', $mock_current_locale );

		// Check state was cleared.
		$this->assertNull( $this->getPrivateProperty( 'original_locale' ) );
		$this->assertNull( $this->getPrivateProperty( 'target_locale' ) );
	}

	/**
	 * Test ihumbak_email_locale filter can override locale.
	 */
	public function test_email_locale_filter_can_override(): void {
		global $mock_wp_options, $mock_current_locale, $mock_wp_filters;

		$mock_wp_options['WPLANG'] = 'nb_NO';
		$mock_current_locale       = 'en_US';

		// Add filter to override locale.
		add_filter(
			'ihumbak_email_locale',
			function ( $locale ) {
				return 'de_DE'; // Override to German.
			}
		);

		$method = $this->getPrivateMethod( 'switchToSiteLocale' );
		$method->invoke( $this->email, 'ihumbak_email_locale' );

		$target_locale = $this->getPrivateProperty( 'target_locale' );
		$this->assertSame( 'de_DE', $target_locale );

		// Clean up filter.
		$mock_wp_filters = array();
	}

	/**
	 * Test locale switches when admin locale differs from site locale.
	 */
	public function test_locale_switches_when_admin_differs_from_site(): void {
		global $mock_wp_options, $mock_current_locale, $mock_locale_stack;

		// Site is Norwegian, but admin is using English.
		$mock_wp_options['WPLANG'] = 'nb_NO';
		$mock_current_locale       = 'en_US';
		$mock_locale_stack         = array();

		$method = $this->getPrivateMethod( 'switchToSiteLocale' );
		$result = $method->invoke( $this->email );

		// Should return true (switch occurred).
		$this->assertTrue( $result );

		// Current locale should now be nb_NO.
		$this->assertSame( 'nb_NO', $mock_current_locale );

		// Original locale should be on the stack.
		$this->assertContains( 'en_US', $mock_locale_stack );
	}

	/**
	 * Test no switch when locales match.
	 */
	public function test_no_switch_when_locales_match(): void {
		global $mock_wp_options, $mock_current_locale, $mock_locale_stack;

		// Both site and current locale are Norwegian.
		$mock_wp_options['WPLANG'] = 'nb_NO';
		$mock_current_locale       = 'nb_NO';
		$mock_locale_stack         = array();

		$method = $this->getPrivateMethod( 'switchToSiteLocale' );
		$result = $method->invoke( $this->email );

		// Should return false (no switch needed).
		$this->assertFalse( $result );

		// Stack should remain empty.
		$this->assertEmpty( $mock_locale_stack );
	}

	/**
	 * Test getSiteLocale returns en_US when WPLANG is empty.
	 */
	public function test_get_site_locale_returns_en_us_when_wplang_empty(): void {
		global $mock_wp_options;
		$mock_wp_options['WPLANG'] = '';

		$method = $this->getPrivateMethod( 'getSiteLocale' );
		$result = $method->invoke( $this->email );

		$this->assertSame( 'en_US', $result );
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
		$result = $method->invoke( $this->email );

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
			'swedish locale'      => array( 'sv_SE', 'sv_SE' ),
		);
	}

	/**
	 * Test email uses site locale, not admin user locale.
	 *
	 * This is the core scenario: admin user has English, site is Norwegian.
	 * Emails should be in Norwegian (site language).
	 */
	public function test_email_locale_follows_site_not_admin(): void {
		global $mock_wp_options, $mock_current_locale;

		// Setup: Site is Norwegian, admin user interface is English.
		$mock_wp_options['WPLANG'] = 'nb_NO';
		$mock_current_locale       = 'en_US'; // Admin user's locale.

		// Switch to site locale.
		$method = $this->getPrivateMethod( 'switchToSiteLocale' );
		$method->invoke( $this->email, 'ihumbak_email_locale' );

		// Target locale should be site locale (Norwegian).
		$target_locale = $this->getPrivateProperty( 'target_locale' );
		$this->assertSame( 'nb_NO', $target_locale );

		// Current locale should have switched.
		$this->assertSame( 'nb_NO', $mock_current_locale );
	}

	/**
	 * Test getSiteLocale returns en_US when WPLANG is not set.
	 */
	public function test_get_site_locale_returns_en_us_when_wplang_not_set(): void {
		// WPLANG not in options at all.
		global $mock_wp_options;
		$mock_wp_options = array();

		$method = $this->getPrivateMethod( 'getSiteLocale' );
		$result = $method->invoke( $this->email );

		$this->assertSame( 'en_US', $result );
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
		$method->invoke( $this->email );

		// Stack should contain original locale.
		$this->assertContains( 'en_US', $mock_locale_stack );
	}
}
