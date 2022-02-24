<?php

namespace Bookboon\JsonLDClient\Tests\Fixtures\Models;

use stdClass;

class ClassWithObjectProperty
{
    private ?stdClass $objectProperty = null;

    public function getObjectProperty(): ?stdClass
    {
        return $this->objectProperty;
    }

    public function setObjectProperty(?stdClass $objectProperty): void
    {
        $this->objectProperty = $objectProperty;
    }
}
