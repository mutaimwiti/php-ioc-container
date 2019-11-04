<?php

namespace Tests;

use Container\Container;
use Tests\Fixtures\ClassA;
use Tests\Fixtures\ClassB;
use Tests\Fixtures\ClassC;
use Tests\Fixtures\ClassE;
use Tests\Fixtures\ClassF;
use Tests\Fixtures\ClassD;
use PHPUnit\Framework\TestCase;
use Container\NotFoundException;
use Container\NoDefaultValueException;


class ContainerTest extends TestCase
{
    /** @var Container */
    protected $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
    }

    /** @test
     * @throws \Exception
     */
    function it_should_bind_and_resolve_arbitrary_values_correctly()
    {
        $this->container->bind('foo', function () {
            return 'bar';
        });

        $this->assertEquals('bar', $this->container->make('foo'));
    }

    /** @test
     * @throws \Exception
     */
    function it_should_throw_if_it_cannot_resolve_item()
    {
        $this->expectException(NotFoundException::class);

        $this->assertEquals('bar', $this->container->make('something'));
    }

    /** @test
     * @throws \Exception
     */
    function it_automatically_instantiates_class_with_empty_constructor()
    {
        $expected = new ClassA();

        $this->assertEquals($expected, $this->container->make(ClassA::class));
    }

    /** @test
     * @throws \Exception
     */
    function it_automatically_instantiates_class_with_class_dependencies()
    {
        $expected = new ClassB(new ClassA());

        $this->assertEquals($expected, $this->container->make(ClassB::class));
    }

    /** @test
     * @throws \Exception
     */
    function it_recursively_instantiates_class_with_any_class_dependencies()
    {
        $expected = new ClassC(new ClassA(), new ClassB(new ClassA()));

        $this->assertEquals($expected, $this->container->make(ClassC::class));
    }

    /** @test */
    function it_throws_for_un_hinted_parameters_without_default_values()
    {
        $this->expectException(NoDefaultValueException::class);

        $this->container->make(ClassD::class);
    }

    /** @test */
    function it_throws_for_for_primitive_types_missing_default_values()
    {
        $this->expectException(NoDefaultValueException::class);

        $this->container->make(ClassE::class);
    }

    /** @test */
    function it_uses_default_values_for_un_hinted_parameters()
    {
        $expected = new ClassF(new ClassA());

        $resolved = $this->container->make(ClassF::class);

        $this->assertEquals($expected->x, $resolved->x);
    }

    /** @test */
    function it_uses_default_value_for_primitive_types()
    {
        $expected = new ClassF(new ClassA());

        $resolved = $this->container->make(ClassF::class);

        $this->assertEquals($expected->x, $resolved->x);
    }

    /** @test */
    function it_allows_binding_of_closures()
    {
        $this->container->bind(ClassD::class, function ($container) {
            return new ClassD($container->make(ClassA::class), 7);
        });

        $expected = new ClassD(new ClassA(), 7);

        $this->assertEquals($expected, $this->container->make(ClassD::class));
    }
}
