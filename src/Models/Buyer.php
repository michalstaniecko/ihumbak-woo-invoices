<?php
/**
 * Buyer Value Object.
 *
 * @package IHumbak\Invoices\Models
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Models;

/**
 * Immutable value object representing buyer data.
 */
final class Buyer {

	/**
	 * Constructor.
	 *
	 * @param string $name     Company or person name.
	 * @param string $address  Street address.
	 * @param string $postcode Postal code.
	 * @param string $city     City.
	 * @param string $country  Country code (default: PL).
	 * @param string $nip      Tax identification number (NIP) - optional for receipts.
	 * @param string $email    Email address.
	 * @param string $phone    Phone number.
	 */
	public function __construct(
		private string $name,
		private string $address = '',
		private string $postcode = '',
		private string $city = '',
		private string $country = 'PL',
		private string $nip = '',
		private string $email = '',
		private string $phone = '',
	) {}

	/**
	 * Get buyer name.
	 *
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Get street address.
	 *
	 * @return string
	 */
	public function getAddress(): string {
		return $this->address;
	}

	/**
	 * Get postal code.
	 *
	 * @return string
	 */
	public function getPostcode(): string {
		return $this->postcode;
	}

	/**
	 * Get city.
	 *
	 * @return string
	 */
	public function getCity(): string {
		return $this->city;
	}

	/**
	 * Get country code.
	 *
	 * @return string
	 */
	public function getCountry(): string {
		return $this->country;
	}

	/**
	 * Get NIP (tax ID).
	 *
	 * @return string
	 */
	public function getNip(): string {
		return $this->nip;
	}

	/**
	 * Check if buyer has NIP.
	 *
	 * @return bool
	 */
	public function hasNip(): bool {
		return ! empty( $this->nip );
	}

	/**
	 * Get email.
	 *
	 * @return string
	 */
	public function getEmail(): string {
		return $this->email;
	}

	/**
	 * Get phone.
	 *
	 * @return string
	 */
	public function getPhone(): string {
		return $this->phone;
	}

	/**
	 * Get full address as single string.
	 *
	 * @return string
	 */
	public function getFullAddress(): string {
		$parts = array_filter(
			array(
				$this->address,
				trim( $this->postcode . ' ' . $this->city ),
				$this->country,
			)
		);

		return implode( ', ', $parts );
	}

	/**
	 * Create from array.
	 *
	 * @param array<string, string> $data Buyer data.
	 * @return self
	 */
	public static function fromArray( array $data ): self {
		return new self(
			name: $data['name'] ?? '',
			address: $data['address'] ?? '',
			postcode: $data['postcode'] ?? '',
			city: $data['city'] ?? '',
			country: $data['country'] ?? 'PL',
			nip: $data['nip'] ?? '',
			email: $data['email'] ?? '',
			phone: $data['phone'] ?? '',
		);
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, string>
	 */
	public function toArray(): array {
		return array(
			'name'     => $this->name,
			'address'  => $this->address,
			'postcode' => $this->postcode,
			'city'     => $this->city,
			'country'  => $this->country,
			'nip'      => $this->nip,
			'email'    => $this->email,
			'phone'    => $this->phone,
		);
	}

	/**
	 * Convert to JSON string.
	 *
	 * @return string
	 */
	public function toJson(): string {
		return (string) wp_json_encode( $this->toArray() );
	}

	/**
	 * Create from JSON string.
	 *
	 * @param string $json JSON string.
	 * @return self
	 */
	public static function fromJson( string $json ): self {
		$data = json_decode( $json, true );
		return self::fromArray( is_array( $data ) ? $data : array() );
	}
}
