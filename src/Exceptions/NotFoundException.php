<?php
/**
 * Not Found Exception.
 *
 * @package IHumbak\Invoices\Exceptions
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Exceptions;

/**
 * Exception thrown when a requested service is not found in the container.
 */
class NotFoundException extends ContainerException {}
