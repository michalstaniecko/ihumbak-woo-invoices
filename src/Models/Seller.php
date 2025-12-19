<?php
/**
 * Seller Value Object.
 *
 * @package IHumbak\Invoices\Models
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Models;

/**
 * Immutable value object representing seller data.
 */
final class Seller {

	/**
	 * Constructor.
	 *
	 * @param string $name         Company name.
	 * @param string $nip          Tax identification number (NIP).
	 * @param string $address      Street address.
	 * @param string $postcode     Postal code.
	 * @param string $city         City.
	 * @param string $country      Country code (default: PL).
	 * @param string $bank_name    Bank name.
	 * @param string $bank_account Bank account number.
	 * @param string $email        Email address.
	 * @param string $phone        Phone number.
	 */
	public function __construct(
		private readonly string $name,
		private readonly string $nip,
		private readonly string $address = '',
		private readonly string $postcode = '',
		private readonly string $city = '',
		private readonly string $country = 'PL',
		private readonly string $bank_name = '',
		private readonly string $bank_account = '',
		private readonly string $email = '',
		private readonly string $phone = '',
	) {}

	/**
	 * Get company name.
	 *
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
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
	 * Get bank name.
	 *
	 * @return string
	 */
	public function getBankName(): string {
		return $this->bank_name;
	}

	/**
	 * Get bank account number.
	 *
	 * @return string
	 */
	public function getBankAccount(): string {
		return $this->bank_account;
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
	 * @param array<string, string> $data Seller data.
	 * @return self
	 */
	public static function fromArray( array $data ): self {
		return new self(
			name: $data['name'] ?? '',
			nip: $data['nip'] ?? '',
			address: $data['address'] ?? '',
			postcode: $data['postcode'] ?? '',
			city: $data['city'] ?? '',
			country: $data['country'] ?? 'PL',
			bank_name: $data['bank_name'] ?? '',
			bank_account: $data['bank_account'] ?? '',
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
			'name'         => $this->name,
			'nip'          => $this->nip,
			'address'      => $this->address,
			'postcode'     => $this->postcode,
			'city'         => $this->city,
			'country'      => $this->country,
			'bank_name'    => $this->bank_name,
			'bank_account' => $this->bank_account,
			'email'        => $this->email,
			'phone'        => $this->phone,
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
