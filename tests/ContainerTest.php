<?php

namespace Tests;

use Container\Container;
use Tests\Fixtures\ClassA;
use Tests\Fixtures\ClassB;
use Tests\Fixtures\ClassC;
use PHPUnit\Framework\TestCase;
use Container\NotFoundException;

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
    function it_should_bind_and_resolve_correctly()
    {
        $this->container->bind('foo', 'bar');

        $this->assertEquals('bar', $this->container->get('foo'));
    }

    /** @test
     * @throws \Exception
     */
    function it_should_throw_if_it_cannot_resolve_item()
    {
        $this->expectException(NotFoundException::class);

        $this->assertEquals('bar', $this->container->get('something'));
    }

    /** @test
     * @throws \Exception
     */
    function it_automatically_instantiates_class_with_empty_constructor()
    {
        $expected = new ClassA();

        $this->assertEquals($expected, $this->container->get(ClassA::class));
    }

    /** @test
     * @throws \Exception
     */
    function it_automatically_instantiates_class_with_class_dependencies()
    {
        $expected = new ClassB(new ClassA());

        $this->assertEquals($expected, $this->container->get(ClassB::class));
    }

    /** @test
     * @throws \Exception
     */
    function it_recursively_instantiates_class_with_any_class_dependencies()
    {
        $expected = new ClassC(new ClassA(), new ClassB(new ClassA()));

        $this->assertEquals($expected, $this->container->get(ClassC::class));
    }

    /** @test
     * @throws \Exception
     */
    function it_uses_existing_bindings_when_loading_class_dependencies_recursively()
    {
        $classA = new ClassA();
        $classA->message = 'Hello world';

        $this->container->bind(ClassA::class, $classA);

        $expected = new ClassC($classA, new ClassB($classA));
        $resolved = $this->container->get(ClassC::class);

        $this->assertEquals($expected->classA, $resolved->classA);
        $this->assertEquals($expected->classB->classA, $resolved->classB->classA);
    }
}
