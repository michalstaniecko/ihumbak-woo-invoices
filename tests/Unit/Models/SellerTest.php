<?php
/**
 * Seller unit tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Models
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Models;

use IHumbak\Invoices\Models\Seller;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Seller value object.
 */
class SellerTest extends TestCase {

	/**
	 * Test constructor with all arguments.
	 */
	public function test_constructor(): void {
		$seller = new Seller(
			name: 'ACME Corp',
			details: "ul. Testowa 1\n00-001 Warszawa\nNIP: 1234567890"
		);

		$this->assertEquals( 'ACME Corp', $seller->getName() );
		$this->assertEquals( "ul. Testowa 1\n00-001 Warszawa\nNIP: 1234567890", $seller->getDetails() );
	}

	/**
	 * Test constructor with minimal arguments.
	 */
	public function test_constructor_with_defaults(): void {
		$seller = new Seller( name: 'Simple Company' );

		$this->assertEquals( 'Simple Company', $seller->getName() );
		$this->assertEquals( '', $seller->getDetails() );
	}

	/**
	 * Test fromArray factory method.
	 */
	public function test_from_array(): void {
		$data = array(
			'name'    => 'Array Company',
			'details' => "Street 1\n12-345 City\nVAT: DE123456789",
		);

		$seller = Seller::fromArray( $data );

		$this->assertEquals( 'Array Company', $seller->getName() );
		$this->assertEquals( "Street 1\n12-345 City\nVAT: DE123456789", $seller->getDetails() );
	}

	/**
	 * Test fromArray with empty array.
	 */
	public function test_from_array_empty(): void {
		$seller = Seller::fromArray( array() );

		$this->assertEquals( '', $seller->getName() );
		$this->assertEquals( '', $seller->getDetails() );
	}

	/**
	 * Test toArray method.
	 */
	public function test_to_array(): void {
		$seller = new Seller(
			name: 'Test Company',
			details: "Test Street\n00-000 Test City"
		);

		$array = $seller->toArray();

		$this->assertEquals(
			array(
				'name'    => 'Test Company',
				'details' => "Test Street\n00-000 Test City",
			),
			$array
		);
	}

	/**
	 * Test toJson method.
	 */
	public function test_to_json(): void {
		$seller = new Seller( name: 'JSON Test', details: 'Some details' );
		$json   = $seller->toJson();

		$this->assertJson( $json );
		$decoded = json_decode( $json, true );
		$this->assertEquals( 'JSON Test', $decoded['name'] );
		$this->assertEquals( 'Some details', $decoded['details'] );
	}

	/**
	 * Test fromJson factory method.
	 */
	public function test_from_json(): void {
		$json   = '{"name":"From JSON","details":"Line1\nLine2"}';
		$seller = Seller::fromJson( $json );

		$this->assertEquals( 'From JSON', $seller->getName() );
		$this->assertEquals( "Line1\nLine2", $seller->getDetails() );
	}

	/**
	 * Test fromJson with invalid JSON.
	 */
	public function test_from_json_invalid(): void {
		$seller = Seller::fromJson( 'not valid json' );

		$this->assertEquals( '', $seller->getName() );
		$this->assertEquals( '', $seller->getDetails() );
	}

	/**
	 * Test immutability.
	 */
	public function test_immutability(): void {
		$seller = new Seller( name: 'Original' );

		$reflection = new \ReflectionClass( $seller );
		$properties = $reflection->getProperties();

		foreach ( $properties as $property ) {
			$this->assertTrue( $property->isReadOnly(), "Property {$property->getName()} should be readonly" );
		}
	}

	/**
	 * Test round-trip fromArray -> toArray.
	 */
	public function test_round_trip(): void {
		$original = array(
			'name'    => 'Round Trip',
			'details' => "Address Line\nNIP: 1234567890\nBank: Test Bank",
		);

		$seller = Seller::fromArray( $original );
		$result = $seller->toArray();

		$this->assertEquals( $original, $result );
	}

	/**
	 * Test handling of very long details text.
	 */
	public function test_long_details_text(): void {
		$long_details = str_repeat( "Line of text with address and bank details.\n", 500 );
		$seller       = new Seller( name: 'Long Details Corp', details: $long_details );

		$this->assertEquals( 'Long Details Corp', $seller->getName() );
		$this->assertEquals( $long_details, $seller->getDetails() );
		$this->assertGreaterThan( 10000, strlen( $seller->getDetails() ) );

		// Verify round-trip preserves long text.
		$array      = $seller->toArray();
		$restored   = Seller::fromArray( $array );
		$this->assertEquals( $long_details, $restored->getDetails() );

		// Verify JSON round-trip.
		$json         = $seller->toJson();
		$from_json    = Seller::fromJson( $json );
		$this->assertEquals( $long_details, $from_json->getDetails() );
	}

	/**
	 * Test handling of special characters in details.
	 */
	public function test_special_characters_in_details(): void {
		$special_details = "Company & Sons <Ltd>\n\"Quoted\" 'Name'\nŁódź Żółć © ® ™\nEmail: test@example.com";
		$seller          = new Seller( name: 'Special Chars', details: $special_details );

		$this->assertEquals( $special_details, $seller->getDetails() );

		// Verify round-trip preserves special characters.
		$json     = $seller->toJson();
		$restored = Seller::fromJson( $json );
		$this->assertEquals( $special_details, $restored->getDetails() );
	}
}
