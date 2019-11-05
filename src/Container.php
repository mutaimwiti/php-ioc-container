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
     * @param bool $shared
     */
    public function bind($abstract, $concrete = null, $shared = true)
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        // drop existing instances
        unset($this->instances[$abstract]);

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * @param $abstract
     * @param null $concrete
     */
    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
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
            return $this->build($abstract);
        }

        return $this->resolve($abstract);
    }

    /**
     * @param $abstract
     * @return bool|mixed
     * @throws NoDefaultValueException
     * @throws ReflectionException
     * @throws ResolutionException
     */
    protected function build($abstract)
    {
        $concrete = $this->getConcrete($abstract);

        if ($concrete instanceof Closure) {
            $instance = $concrete($this);
        } else if ($abstract === $concrete) {
            // if abstract === concrete we resolve abstract
            $instance = $this->resolve($concrete);
        } else {
            // there is a possibility that this concrete aliases another concrete
            // in which case we want to follow any nests
            // elaboration: given bind(X, Y) and bind(Z, X) , resolve Z should give Y
            $instance = $this->make($concrete);
        }

        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $instance;
        }

        return $instance;
    }

    /**
     * @param $abstract
     * @return mixed
     */
    protected function getConcrete($abstract)
    {
        return $this->bindings[$abstract]['concrete'];
    }

    /**
     * @param $abstract
     * @return bool
     */
    protected function isShared($abstract)
    {
        return $this->bindings[$abstract]['shared'] === true;
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
