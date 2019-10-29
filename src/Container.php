<?php

namespace Container;

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
        }
        throw new NotFoundException("No ${key} is defined on container");
    }
}
