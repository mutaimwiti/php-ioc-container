<?php

namespace Tests;

use Container\Container;
use Tests\Fixtures\ClassA;
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
        $this->assertEquals(new ClassA(), $this->container->get(ClassA::class));
    }
}
