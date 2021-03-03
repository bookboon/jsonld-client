<?php


namespace Bookboon\JsonLDClient\Tests\Fixtures\Models;


class NestedClass
{
    protected string $string = '';
    protected ?SimpleClass $simpleClass = null;

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
     * @return SimpleClass|null
     */
    public function getSimpleClass() : ?SimpleClass
    {
        return $this->simpleClass;
    }

    /**
     * @param SimpleClass|null $simpleClass
     * @return void
     */
    public function setSimpleClass(?SimpleClass $simpleClass): void
    {
        $this->simpleClass = $simpleClass;
    }
}
