<?php

namespace Tests\Fixtures\Classes;

/**
 * Class ClassC
 * @package Tests\Fixtures
 * @property ClassA $classA
 * @property ClassB $classB
 */
class ClassC {
    use GetsAndSets;

    private $classA;
    private $classB;

    public function __construct(ClassA $classA, ClassB $classB)
    {
        $this->classA = $classA;
        $this->classB = $classB;
    }
}
