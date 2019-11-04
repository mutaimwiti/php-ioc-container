<?php

namespace Tests\Fixtures\Classes;

/**
 * Class ClassE
 * @package Tests\Fixtures
 * @property ClassA $classA
 * @property int $x
 */
class classG
{
    use GetsAndSets;

    protected $classA;
    protected $x;

    public function __construct(ClassA $classA, $x = 10)
    {
        $this->classA = $classA;
        $this->x = $x;
    }
}
