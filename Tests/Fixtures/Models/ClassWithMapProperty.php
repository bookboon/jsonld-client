<?php

namespace Bookboon\JsonLDClient\Tests\Fixtures\Models;

use ArrayObject;

class ClassWithMapProperty
{
    private ?ArrayObject $objectProperty = null;

    public function getObjectProperty(): ?ArrayObject
    {
        return $this->objectProperty;
    }

    public function setObjectProperty(?ArrayObject $objectProperty): void
    {
        $this->objectProperty = $objectProperty;
    }
}
