<?php

namespace Tests\Fixtures\Classes;

class ClassE
{
    protected $classA;
    protected $x;

    public function __construct(ClassA $classA, int $x)
    {
        $this->classA = $classA;
        $this->x = $x;
    }
}
