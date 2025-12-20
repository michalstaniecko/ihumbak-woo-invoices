<?php
/**
 * Buyer unit tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Models
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Models;

use IHumbak\Invoices\Models\Buyer;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Buyer value object.
 */
class BuyerTest extends TestCase {

	/**
	 * Test constructor with all arguments.
	 */
	public function test_constructor(): void {
		$buyer = new Buyer(
			name: 'ACME Corp',
			address: 'ul. Testowa 1',
			postcode: '00-001',
			city: 'Warszawa',
			country: 'PL',
			nip: '1234567890',
			email: 'contact@acme.pl',
			phone: '+48 123 456 789'
		);

		$this->assertEquals( 'ACME Corp', $buyer->getName() );
		$this->assertEquals( 'ul. Testowa 1', $buyer->getAddress() );
		$this->assertEquals( '00-001', $buyer->getPostcode() );
		$this->assertEquals( 'Warszawa', $buyer->getCity() );
		$this->assertEquals( 'PL', $buyer->getCountry() );
		$this->assertEquals( '1234567890', $buyer->getNip() );
		$this->assertEquals( 'contact@acme.pl', $buyer->getEmail() );
		$this->assertEquals( '+48 123 456 789', $buyer->getPhone() );
	}

	/**
	 * Test constructor with minimal arguments.
	 */
	public function test_constructor_with_defaults(): void {
		$buyer = new Buyer( name: 'Simple Client' );

		$this->assertEquals( 'Simple Client', $buyer->getName() );
		$this->assertEquals( '', $buyer->getAddress() );
		$this->assertEquals( '', $buyer->getPostcode() );
		$this->assertEquals( '', $buyer->getCity() );
		$this->assertEquals( 'PL', $buyer->getCountry() );
		$this->assertEquals( '', $buyer->getNip() );
		$this->assertEquals( '', $buyer->getEmail() );
		$this->assertEquals( '', $buyer->getPhone() );
	}

	/**
	 * Test hasNip method.
	 */
	public function test_has_nip(): void {
		$with_nip    = new Buyer( name: 'Company', nip: '1234567890' );
		$without_nip = new Buyer( name: 'Person' );

		$this->assertTrue( $with_nip->hasNip() );
		$this->assertFalse( $without_nip->hasNip() );
	}

	/**
	 * Test getFullAddress method.
	 */
	public function test_get_full_address(): void {
		$buyer = new Buyer(
			name: 'Test',
			address: 'ul. Przykładowa 10',
			postcode: '01-234',
			city: 'Kraków',
			country: 'PL'
		);

		$expected = 'ul. Przykładowa 10, 01-234 Kraków, PL';
		$this->assertEquals( $expected, $buyer->getFullAddress() );
	}

	/**
	 * Test getFullAddress with partial data.
	 */
	public function test_get_full_address_partial(): void {
		$buyer = new Buyer(
			name: 'Test',
			city: 'Gdańsk'
		);

		$expected = 'Gdańsk, PL';
		$this->assertEquals( $expected, $buyer->getFullAddress() );
	}

	/**
	 * Test fromArray factory method.
	 */
	public function test_from_array(): void {
		$data = array(
			'name'     => 'Array Company',
			'address'  => 'Street 1',
			'postcode' => '12-345',
			'city'     => 'City',
			'country'  => 'DE',
			'nip'      => 'DE123456789',
			'email'    => 'test@example.com',
			'phone'    => '123456789',
		);

		$buyer = Buyer::fromArray( $data );

		$this->assertEquals( 'Array Company', $buyer->getName() );
		$this->assertEquals( 'Street 1', $buyer->getAddress() );
		$this->assertEquals( '12-345', $buyer->getPostcode() );
		$this->assertEquals( 'City', $buyer->getCity() );
		$this->assertEquals( 'DE', $buyer->getCountry() );
		$this->assertEquals( 'DE123456789', $buyer->getNip() );
		$this->assertEquals( 'test@example.com', $buyer->getEmail() );
		$this->assertEquals( '123456789', $buyer->getPhone() );
	}

	/**
	 * Test fromArray with empty array.
	 */
	public function test_from_array_empty(): void {
		$buyer = Buyer::fromArray( array() );

		$this->assertEquals( '', $buyer->getName() );
		$this->assertEquals( 'PL', $buyer->getCountry() );
	}

	/**
	 * Test toArray method.
	 */
	public function test_to_array(): void {
		$buyer = new Buyer(
			name: 'Test Company',
			address: 'Test Street',
			postcode: '00-000',
			city: 'Test City',
			country: 'PL',
			nip: '1111111111',
			email: 'email@test.pl',
			phone: '111222333'
		);

		$array = $buyer->toArray();

		$this->assertEquals(
			array(
				'name'     => 'Test Company',
				'address'  => 'Test Street',
				'postcode' => '00-000',
				'city'     => 'Test City',
				'country'  => 'PL',
				'nip'      => '1111111111',
				'email'    => 'email@test.pl',
				'phone'    => '111222333',
			),
			$array
		);
	}

	/**
	 * Test toJson method.
	 */
	public function test_to_json(): void {
		$buyer = new Buyer( name: 'JSON Test', nip: '123' );
		$json  = $buyer->toJson();

		$this->assertJson( $json );
		$decoded = json_decode( $json, true );
		$this->assertEquals( 'JSON Test', $decoded['name'] );
		$this->assertEquals( '123', $decoded['nip'] );
	}

	/**
	 * Test fromJson factory method.
	 */
	public function test_from_json(): void {
		$json  = '{"name":"From JSON","nip":"9999999999","city":"Poznań"}';
		$buyer = Buyer::fromJson( $json );

		$this->assertEquals( 'From JSON', $buyer->getName() );
		$this->assertEquals( '9999999999', $buyer->getNip() );
		$this->assertEquals( 'Poznań', $buyer->getCity() );
	}

	/**
	 * Test fromJson with invalid JSON.
	 */
	public function test_from_json_invalid(): void {
		$buyer = Buyer::fromJson( 'not valid json' );

		$this->assertEquals( '', $buyer->getName() );
	}

	/**
	 * Test immutability.
	 */
	public function test_immutability(): void {
		$buyer = new Buyer( name: 'Original' );

		// Value object should be immutable - verify readonly properties
		$reflection = new \ReflectionClass( $buyer );
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
			'name'     => 'Round Trip',
			'address'  => 'Address',
			'postcode' => '00-000',
			'city'     => 'City',
			'country'  => 'PL',
			'nip'      => '1234567890',
			'email'    => 'test@test.pl',
			'phone'    => '123456789',
		);

		$buyer  = Buyer::fromArray( $original );
		$result = $buyer->toArray();

		$this->assertEquals( $original, $result );
	}
}
