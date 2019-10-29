<?php

namespace Tests\Fixtures;

/**
 * Class ClassB
 * @package Tests\Fixtures
 * @property ClassA $classA
 */
class ClassB {
    use GetsAndSets;

    private $classA;

    public function __construct(ClassA $classA)
    {
        $this->classA = $classA;
    }
}
