<?php

namespace Container;

use ReflectionClass;
use ReflectionFunction;

class Container
{
    protected $bindings = [];

    /**
     * @param $key
     * @param $value
     */
    public function bind($key, $value)
    {
        $this->bindings[$key] = $value;
    }

    /**
     * @param $key
     * @return mixed
     * @throws \Exception
     */
    public function get($key)
    {
        if (array_key_exists($key, $this->bindings)) {
            return $this->bindings[$key];
        } else if ($instance = $this->resolve($key)) {
            return $instance;
        }
        throw new NotFoundException("No ${key} is defined on container");
    }

    /**
     * @param $key
     * @return bool
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function resolve($key)
    {
        if (class_exists($key)) {
            $reflectionClass = new ReflectionClass($key);

            if ($reflectionClass->isInstantiable()) {
                $constructor = $reflectionClass->getConstructor();

                $parameters = $constructor->getParameters();

                $arguments = [];

                foreach ($parameters as $parameter) {
                    $class = $parameter->getClass();

                    if ($class !== null) {
                        $arguments[] = $this->get($class->name);
                    } else if ($parameter->isDefaultValueAvailable()) {
                        $arguments[] = $parameter->getDefaultValue();
                    } else {
                        throw new NoDefaultValueException(
                            "Parameter $parameter->name of $key has no default value"
                        );
                    }
                }

                return new $key(...$arguments);
            }
        }
        return false;
    }
}
