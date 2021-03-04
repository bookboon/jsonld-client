<?php


namespace Bookboon\JsonLDClient\Tests\Fixtures\Models;


class NestedClassWithoutDoc
{
    protected string $string = '';
    protected ?SimpleClass $simpleClass = null;

    public function getString() : string
    {
        return $this->string;
    }

    public function setString(string $string): void
    {
        $this->string = $string;
    }

    public function getSimpleClass() : ?SimpleClass
    {
        return $this->simpleClass;
    }

    public function setSimpleClass(?SimpleClass $simpleClass): void
    {
        $this->simpleClass = $simpleClass;
    }
}
