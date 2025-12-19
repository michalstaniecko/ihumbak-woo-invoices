<?php
/**
 * Container unit tests.
 *
 * @package IHumbak\Invoices\Tests\Unit\Core
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Tests\Unit\Core;

use IHumbak\Invoices\Core\Container;
use IHumbak\Invoices\Exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Test case for Container class.
 */
class ContainerTest extends TestCase {

    /**
     * Container instance.
     *
     * @var Container
     */
    private Container $container;

    /**
     * Set up test fixtures.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->container = new Container();
    }

    /**
     * Test registering and retrieving a service.
     *
     * @return void
     */
    public function test_can_register_and_get_service(): void {
        $this->container->register( 'test_service', fn() => new \stdClass() );

        $service = $this->container->get( 'test_service' );

        $this->assertInstanceOf( \stdClass::class, $service );
    }

    /**
     * Test that same instance is returned on subsequent calls.
     *
     * @return void
     */
    public function test_returns_same_instance(): void {
        $this->container->register( 'singleton', fn() => new \stdClass() );

        $first  = $this->container->get( 'singleton' );
        $second = $this->container->get( 'singleton' );

        $this->assertSame( $first, $second );
    }

    /**
     * Test make() creates new instance each time.
     *
     * @return void
     */
    public function test_make_creates_new_instance(): void {
        $this->container->register( 'factory', fn() => new \stdClass() );

        $first  = $this->container->make( 'factory' );
        $second = $this->container->make( 'factory' );

        $this->assertNotSame( $first, $second );
    }

    /**
     * Test has() returns true for registered services.
     *
     * @return void
     */
    public function test_has_returns_true_for_registered(): void {
        $this->container->register( 'exists', fn() => new \stdClass() );

        $this->assertTrue( $this->container->has( 'exists' ) );
    }

    /**
     * Test has() returns false for unregistered services.
     *
     * @return void
     */
    public function test_has_returns_false_for_unregistered(): void {
        $this->assertFalse( $this->container->has( 'not_exists' ) );
    }

    /**
     * Test get() throws exception for unregistered service.
     *
     * @return void
     */
    public function test_get_throws_not_found_exception(): void {
        $this->expectException( NotFoundException::class );

        $this->container->get( 'undefined_service' );
    }

    /**
     * Test set() can directly set an instance.
     *
     * @return void
     */
    public function test_can_set_instance_directly(): void {
        $instance = new \stdClass();
        $instance->value = 'test';

        $this->container->set( 'direct', $instance );

        $retrieved = $this->container->get( 'direct' );

        $this->assertSame( $instance, $retrieved );
        $this->assertEquals( 'test', $retrieved->value );
    }

    /**
     * Test factory receives container instance.
     *
     * @return void
     */
    public function test_factory_receives_container(): void {
        $this->container->register( 'dependency', fn() => new \stdClass() );
        $this->container->register(
            'dependent',
            function ( Container $c ) {
                $obj = new \stdClass();
                $obj->dependency = $c->get( 'dependency' );
                return $obj;
            }
        );

        $service = $this->container->get( 'dependent' );

        $this->assertInstanceOf( \stdClass::class, $service->dependency );
    }
}
