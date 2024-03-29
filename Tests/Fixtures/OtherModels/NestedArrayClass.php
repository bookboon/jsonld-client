<?php


namespace Bookboon\JsonLDClient\Tests\Fixtures\OtherModels;


class NestedArrayClass
{
    protected string $string = '';
    protected array $simpleClasses = [];

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
