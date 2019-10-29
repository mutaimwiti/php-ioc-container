<?php

namespace Tests\Fixtures;

/**
 * Class ClassE
 * @package Tests\Fixtures
 * @property ClassA $classA
 * @property int $x
 */
class ClassF
{
    use GetsAndSets;

    protected $classA;
    protected $x;

    public function __construct(ClassA $classA, $x = 'My value')
    {
        $this->classA = $classA;
        $this->x = $x;
    }
}
