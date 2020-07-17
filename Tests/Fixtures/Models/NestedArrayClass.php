<?php


namespace Bookboon\JsonLDClient\Tests\Fixtures\Models;


class NestedArrayClass
{
    protected $string;
    protected $simpleClasses;

    /**
     * @return string
     */
    public function getString() : string
    {
        return $this->string;
    }

    /**
     * @param string $string
     * @return void
     */
    public function setString(string $string): void
    {
        $this->string = $string;
    }

    /**
     * @return SimpleClass[]
     */
    public function getSimpleClasses() : array
    {
        return $this->simpleClasses;
    }

    /**
     * @param SimpleClass[] $simpleClasses
     * @return void
     */
    public function setSimpleClasses(array $simpleClasses): void
    {
        $this->simpleClasses = $simpleClasses;
    }
}
