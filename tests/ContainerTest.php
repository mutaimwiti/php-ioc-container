<?php

namespace Tests;

use ReflectionException;
use Container\Container;
use PHPUnit\Framework\TestCase;
use Container\ResolutionException;
use Tests\Fixtures\Classes\Class1;
use Tests\Fixtures\Classes\ClassA;
use Tests\Fixtures\Classes\ClassB;
use Tests\Fixtures\Classes\ClassC;
use Tests\Fixtures\Classes\ClassE;
use Tests\Fixtures\Classes\ClassF;
use Tests\Fixtures\Classes\ClassD;
use Tests\Fixtures\Classes\classH;
use Container\NoDefaultValueException;
use Tests\Fixtures\Contracts\Contract1;
use Tests\Fixtures\Contracts\Contract2;


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
        $this->expectException(ReflectionException::class);

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
    function it_automatically_instantiates_class_without_constructor()
    {
        $expected = new classH();

        $this->assertEquals($expected, $this->container->make(classH::class));
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

    /** @test */
    function it_binds_abstract_to_itself_if_no_concrete_is_provided() {
        $this->container->bind(ClassA::class);

        $expected = new ClassA();

        $this->assertEquals($expected, $this->container->make(ClassA::class));
    }

    /** @test */
    function it_throws_for_interface_without_binding() {
        $this->expectException(ResolutionException::class);

        $this->container->make(Contract1::class);
    }

    /** @test */
    function it_throws_for_interface_bound_to_interface() {
        $this->expectException(ResolutionException::class);

        $this->container->bind(Contract1::class, Contract2::class);

        $this->container->make(Contract1::class);
    }

    /** @test */
    function it_correctly_resolves_interface_bound_to_concrete() {
        $this->container->bind(Contract1::class, Class1::class);

        $expected = new Class1();

        $this->assertEquals($expected, $this->container->make(Contract1::class));
    }

    /** @test */
    function it_follows_nested_bindings_to_resolve_correct_type() {
        $this->container->bind(Contract1::class, Class1::class);
        $this->container->bind(Contract2::class, Contract1::class);

        $expected = new Class1();

        $this->assertEquals($expected, $this->container->make(Contract2::class));
    }

    /** @test */
    function it_allows_binding_to_instances() {
        $classA = new ClassA();

        $this->container->instance(ClassA::class, $classA);

        $resolved = $this->container->make(ClassA::class);

        $this->assertEquals(spl_object_hash($classA), spl_object_hash($resolved));
    }

    /** @test
     * @throws \Exception
     */
    function it_uses_bound_instances_when_loading_class_dependencies()
    {
        $classA = new ClassA();
        $classA->message = 'Hello world';

        $this->container->instance(ClassA::class, $classA);

        $expected = new ClassC($classA, new ClassB($classA));
        $resolved = $this->container->make(ClassC::class);

        $this->assertEquals($expected->classA, $resolved->classA);
        $this->assertEquals($expected->classB->classA, $resolved->classB->classA);
    }
}
