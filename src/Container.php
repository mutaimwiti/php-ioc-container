<?php

namespace Container;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class Container
{
    protected $bindings = [];
    protected $instances = [];

    /**
     * @param $abstract
     * @param $concrete
     */
    public function bind($abstract, $concrete = null)
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = $concrete;
    }

    /**
     * @param $abstract
     * @param $instance
     */
    public function instance($abstract, $instance)
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * @param $abstract
     * @return bool|mixed
     * @throws NoDefaultValueException
     * @throws ReflectionException
     * @throws ResolutionException
     */
    public function make($abstract)
    {
        // we prioritize instances. If an instance exists we return it
        if (array_key_exists($abstract, $this->instances)) {
            return $this->instances[$abstract];
        }
        // if no instance exists we try ro resolve from bindings
        if (array_key_exists($abstract, $this->bindings)) {
            $concrete = $this->bindings[$abstract];

            if ($concrete instanceof Closure) {
                return $concrete($this);
            }
            // if abstract === concrete we resolve abstract
            if ($abstract === $concrete) {
                return $this->resolve($concrete);
            }

            // there is a possibility that this concrete aliases another concrete
            // in which case we want to follow any nests
            // elaboration: given bind(X, Y) and bind(Z, X) , resolve Z should give Y
            return $this->make($concrete);
        }

        return $this->resolve($abstract);
    }

    /**
     * @param $abstract
     * @return bool
     * @throws NoDefaultValueException
     * @throws ReflectionException
     * @throws ResolutionException
     */
    protected function resolve($abstract)
    {
        $reflectionClass = new ReflectionClass($abstract);

        if ($reflectionClass->isInstantiable()) {
            $constructor = $reflectionClass->getConstructor();

            $arguments = [];

            if ($constructor !== null) {
                $parameters = $constructor->getParameters();

                foreach ($parameters as $parameter) {
                    $arguments[] = $this->resolveParameterArgument($parameter);
                }
            }

            return new $abstract(...$arguments);
        } else {
            throw new ResolutionException("[$abstract] is not instantiable");
        }
    }

    /**
     * @param ReflectionParameter $parameter
     * @return mixed
     * @throws NoDefaultValueException
     * @throws ReflectionException
     * @throws ResolutionException
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
