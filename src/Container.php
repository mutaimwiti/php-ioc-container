<?php

namespace Container;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class Container
{
    protected $bindings = [];

    /**
     * @param $abstract
     * @param $concrete
     */
    public function bind($abstract, $concrete)
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * @param $abstract
     * @return bool|mixed
     * @throws NoDefaultValueException
     * @throws ReflectionException
     */
    public function make($abstract)
    {
        if (array_key_exists($abstract, $this->bindings)) {
            $concrete = $this->bindings[$abstract];

            if ($concrete instanceof Closure) {
                return $concrete($this);
            }

            return $this->resolve($abstract);
        }

        return $this->resolve($abstract);
    }

    /**
     * @param $abstract
     * @return bool
     * @throws NoDefaultValueException
     * @throws ReflectionException
     */
    protected function resolve($abstract)
    {
        $reflectionClass = new ReflectionClass($abstract);

        if ($reflectionClass->isInstantiable()) {
            $constructor = $reflectionClass->getConstructor();

            $parameters = $constructor->getParameters();

            $arguments = [];

            foreach ($parameters as $parameter) {
                $arguments[] = $this->resolveParameterArgument($parameter);
            }

            return new $abstract(...$arguments);
        }
    }

    /**
     * @param ReflectionParameter $parameter
     * @return mixed
     * @throws NoDefaultValueException
     * @throws ReflectionException
     */
    protected function resolveParameterArgument(ReflectionParameter $parameter)
    {
        $class = $parameter->getClass();

        if ($class !== null) {
            return $this->make($class->name);
        } else if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        } else {
            throw new NoDefaultValueException(
                "Parameter $parameter->name has no default value"
            );
        }
    }
}
