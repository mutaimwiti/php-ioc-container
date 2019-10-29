<?php

namespace Tests\Fixtures;

trait GetsAndSets
{
    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            return $this->$property = $value;
        }

        return $this;
    }

    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }

        return null;
    }
}
