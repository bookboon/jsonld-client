<?php

namespace Bookboon\JsonLDClient\Tests\Fixtures\Models;

class CircularParent
{
    protected array $children = [];

    /**
     * @return CircularChild[]
     */
    public function getChildren() : array
    {
        return $this->children;
    }

    /**
     * @param CircularChild[] $children
     * @return void
     */
    public function setChildren(array $children): void
    {
        $this->children = $children;
    }
}
