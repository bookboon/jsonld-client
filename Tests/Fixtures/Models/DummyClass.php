<?php

namespace Bookboon\JsonLDClient\Tests\Fixtures\Models;

class DummyClass
{
    private string $name = '';

    private ?ChildClass $childClass = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getChildClass(): ?ChildClass
    {
        return $this->childClass;
    }

    public function setChildClass(?ChildClass $childClass): void
    {
        $this->childClass = $childClass;
    }
}
