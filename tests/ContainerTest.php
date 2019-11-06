<?php

namespace Tests;

use LogicException;
use ReflectionException;
use Container\Container;
use PHPUnit\Framework\TestCase;
use Container\ResolutionException;
use Tests\Fixtures\Classes\Class1;
use Tests\Fixtures\Classes\Class2;
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
use Tests\Fixtures\Contracts\Contract3;


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

    /** @test */
    function it_should_register_and_resolve_arbitrary_values_as_instances()
    {
        $this->container->instance('hello', 'world');

        $this->assertEquals('world', $this->container->make('hello'));
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
    function it_binds_abstract_to_itself_if_no_concrete_is_provided()
    {
        $this->container->bind(ClassA::class);

        $expected = new ClassA();

        $this->assertEquals($expected, $this->container->make(ClassA::class));
    }

    /** @test */
    function it_throws_for_interface_without_binding()
    {
        $this->expectException(ResolutionException::class);

        $this->container->make(Contract1::class);
    }

    /** @test */
    function it_throws_for_interface_bound_to_interface()
    {
        $this->expectException(ResolutionException::class);

        $this->container->bind(Contract1::class, Contract2::class);

        $this->container->make(Contract1::class);
    }

    /** @test */
    function it_correctly_resolves_interface_bound_to_concrete()
    {
        $this->container->bind(Contract1::class, Class1::class);

        $expected = new Class1();

        $this->assertEquals($expected, $this->container->make(Contract1::class));
    }

    /** @test */
    function it_follows_nested_bindings_to_resolve_correct_type()
    {
        $this->container->bind(Contract1::class, Class1::class);
        $this->container->bind(Contract2::class, Contract1::class);
        $this->container->bind(Contract3::class, Contract2::class);

        $expected = new Class1();

        $this->assertEquals($expected, $this->container->make(Contract3::class));
    }

    /** @test */
    function it_allows_binding_to_instances()
    {
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

    /** @test */
    function it_binds_singletons()
    {
        $this->container->singleton(ClassA::class);

        $resolved = $this->container->make(ClassA::class);
        $resolved2 = $this->container->make(ClassA::class);

        $this->assertEquals(spl_object_hash($resolved), spl_object_hash($resolved2));
    }

    /** @test */
    function it_drops_existing_instances_when_bindings_are_registered()
    {
        $classA = new ClassA();

        $this->container->instance(ClassA::class, $classA);
        $this->container->bind(ClassA::class);

        $resolved = $this->container->make(ClassA::class);

        $this->assertNotEquals(spl_object_hash($classA), spl_object_hash($resolved));
    }

    /** @test */
    function it_drops_existing_aliases_when_bindings_are_registered()
    {
        // alias ClassA with foo
        $this->container->alias(ClassA::class, 'foo');
        $resolved = $this->container->make('foo');
        $this->assertInstanceOf(ClassA::class, $resolved);

        // bind foo to ClassB - since foo is now bound to ClassB it ceases to alias ClassA
        $this->container->bind('foo', ClassB::class);
        $resolved = $this->container->make('foo');
        $this->assertInstanceOf(ClassB::class, $resolved);
    }

    /** @test */
    function it_should_allow_aliasing_of_types()
    {
        $this->container->singleton(ClassA::class);
        $this->container->alias(ClassA::class, 'ca');

        $resolved = $this->container->make('ca');
        $expected = new ClassA();

        $this->assertEquals($expected, $resolved);
    }

    /** @test */
    function it_should_allow_nested_aliasing()
    {
        $this->container->singleton(ClassA::class);
        $this->container->alias(ClassA::class, 'x');
        $this->container->alias('x', 'y');
        $this->container->alias('y', 'z');

        $resolved = $this->container->make('z');
        $expected = new ClassA();

        $this->assertEquals($expected, $resolved);
    }

    /** @test */
    function it_should_not_allow_self_aliasing()
    {
        $this->expectException(LogicException::class);

        $this->container->alias(ClassA::class, ClassA::class);
    }

    /** @test */
    function it_should_allow_creation_of_a_globally_available_instance()
    {
        $instance = Container::getInstance();

        $this->assertEquals(spl_object_hash($instance), spl_object_hash(Container::getInstance()));
    }

    /** @test */
    function it_should_clear_all_bindings_when_flush_is_invoked()
    {
        $this->expectException(ResolutionException::class);

        $this->container->bind(Contract1::class, Class1::class);
        $this->container->flush();
        $this->container->make(Contract1::class);
    }

    /** @test */
    function it_should_clear_all__registered_instances_when_flush_is_invoked()
    {
        $this->expectException(ResolutionException::class);

        $this->container->instance(Contract1::class, Class1::class);
        $this->container->flush();
        $this->container->make(Contract1::class);
    }

    /** @test */
    function it_should_clear_all_resolved_instances_when_flush_is_invoked()
    {
        $this->expectException(ResolutionException::class);

        $this->container->singleton(Contract1::class, Class1::class);
        $resolved = $this->container->make(Contract1::class);

        $this->assertInstanceOf(Class1::class, $resolved);

        $this->container->flush();
        $this->container->make(Contract1::class);
    }

    /** @test */
    function it_should_clear_all_aliases_when_flush_is_invoked()
    {
        $this->expectException(ReflectionException::class);

        $this->container->alias(ClassA::class, 'foo');
        $this->container->flush();
        $this->container->make('foo');
    }

    /** @test */
    function it_should_forget_a_specific_instance_when_forget_instance_is_invoked()
    {
        $this->expectException(ResolutionException::class);

        $this->container->instance(Contract1::class, new Class1());
        $this->container->forgetInstance(Contract1::class);
        $this->container->make(Contract1::class);
    }

    /** @test */
    function it_should_forget_all_instances_when_forget_instances_is_invoked()
    {
        $this->container->instance(Contract1::class, new Class1());
        $this->container->instance(Contract2::class, new Class2());

        $this->container->forgetInstances();

        $abstracts = [Contract1::class, Contract2::class];

        foreach ($abstracts as $abstract) {
            try {
                $this->container->make($abstract);
            } catch (\Exception $exception) {
                $this->assertInstanceOf(ResolutionException::class, $exception);
            }
        }
    }

    /** @test */
    function it_allows_array_set_and_access()
    {
        // set value - bind
        $this->container[Contract1::class] = Class1::class;
        // access value - make
        $this->assertInstanceOf(Class1::class, $this->container[Contract1::class]);
    }

    /** @test */
    function it_allows_array_un_set()
    {
        $this->expectException(ResolutionException::class);
        // set value - bind
        $this->container[Contract1::class] = Class1::class;
        // access value - make
        $this->assertInstanceOf(Class1::class, $this->container[Contract1::class]);
        // un-set key
        unset($this->container[Contract1::class]);
        // attempt to access unset value - make
        $this->container[Contract1::class];
    }
}
