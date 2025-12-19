<?php
/**
 * Dependency Injection Container.
 *
 * @package IHumbak\Invoices\Core
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Core;

use IHumbak\Invoices\Exceptions\ContainerException;
use IHumbak\Invoices\Exceptions\NotFoundException;

/**
 * Simple DI Container.
 */
class Container {

	/**
	 * Registered service factories.
	 *
	 * @var array<string, callable>
	 */
	private array $factories = array();

	/**
	 * Resolved service instances.
	 *
	 * @var array<string, object>
	 */
	private array $instances = array();

	/**
	 * Register a service factory.
	 *
	 * @param string   $id      Service identifier.
	 * @param callable $factory Factory function.
	 * @return void
	 */
	public function register( string $id, callable $factory ): void {
		$this->factories[ $id ] = $factory;
	}

	/**
	 * Get a service from the container.
	 *
	 * @param string $id Service identifier.
	 * @return object
	 * @throws NotFoundException If service not found.
	 */
	public function get( string $id ): object {
		if ( isset( $this->instances[ $id ] ) ) {
			return $this->instances[ $id ];
		}

		if ( ! isset( $this->factories[ $id ] ) ) {
			throw new NotFoundException(
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message, not output.
				sprintf( 'Service "%s" not found in container.', $id )
			);
		}

		$this->instances[ $id ] = ( $this->factories[ $id ] )( $this );

		return $this->instances[ $id ];
	}

	/**
	 * Check if a service exists.
	 *
	 * @param string $id Service identifier.
	 * @return bool
	 */
	public function has( string $id ): bool {
		return isset( $this->factories[ $id ] ) || isset( $this->instances[ $id ] );
	}

	/**
	 * Set an instance directly.
	 *
	 * @param string $id       Service identifier.
	 * @param object $instance Service instance.
	 * @return void
	 */
	public function set( string $id, object $instance ): void {
		$this->instances[ $id ] = $instance;
	}

	/**
	 * Create a new instance without caching.
	 *
	 * @param string $id Service identifier.
	 * @return object
	 * @throws NotFoundException If service not found.
	 */
	public function make( string $id ): object {
		if ( ! isset( $this->factories[ $id ] ) ) {
			throw new NotFoundException(
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message, not output.
				sprintf( 'Service "%s" not found in container.', $id )
			);
		}

		return ( $this->factories[ $id ] )( $this );
	}
}
