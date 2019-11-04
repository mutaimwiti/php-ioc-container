<?php

namespace Tests\Fixtures\Classes;

/**
 * Class ClassA
 * @package Tests\Fixtures
 * @property  string $message
 */
class ClassA
{
    use GetsAndSets;

    protected $message;

    public function __construct()
    {
        $this->message = 'bar';
    }
}
