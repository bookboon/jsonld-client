<?php

namespace Bookboon\JsonLDClient\Tests\Fixtures\Models;

class CircularChild
{
    protected $parent;
    protected $value;

    /**
     * @return string
     */
    public function getValue() : string
    {
        return $this->value;
    }

    /**
     * @param string $value
     * return @void
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * @return CircularParent|null
     */
    public function getParent() : ?CircularParent
    {
        return $this->parent;
    }

    /**
     * @param CircularParent|null $parent
     * @return void
     */
    public function setParent(?CircularParent$parent): void
    {
        $this->parent = $parent;
    }


}
