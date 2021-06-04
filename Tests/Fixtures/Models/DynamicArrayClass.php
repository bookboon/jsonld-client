<?php

namespace Bookboon\JsonLDClient\Tests\Fixtures\Models;

class DynamicArrayClass
{
    protected array $array = [];

    /**
     * @return array
     */
    public function getArray() : array
    {
        return $this->array;
    }

    /**
     * @param $array
     */
    public function setArray($array): void
    {
        $this->array = $array;
    }

    public function getId() : string
    {
        return '3e147484-01fd-4176-b8f4-43ef623fb092';
    }
}
