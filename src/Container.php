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

    public function resolve($class)
    {
        if (class_exists($class)) {
            $reflectionClass = new ReflectionClass($class);

            if ($reflectionClass->isInstantiable()) {
                $constructor = $reflectionClass->getConstructor();

                $parameters = $constructor->getParameters();

                if (!count($parameters)) {
                    return new $class();
                }

                $arguments = [];

                foreach ($parameters as $parameter) {
                    $arguments[] = $this->get(($parameter->getClass())->name);
                }

                return new $class(...$arguments);
            }
        }
        return false;
    }
}
