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
	 * @param string $name    Company name.
	 * @param string $details Company details (address, VAT ID, bank, phone, etc.).
	 */
	public function __construct(
		private readonly string $name,
		private readonly string $details = '',
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
	 * Get company details.
	 *
	 * @return string
	 */
	public function getDetails(): string {
		return $this->details;
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
			details: $data['details'] ?? '',
		);
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, string>
	 */
	public function toArray(): array {
		return array(
			'name'    => $this->name,
			'details' => $this->details,
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
