<?php

namespace Bookboon\JsonLDClient\Tests\Fixtures\Models;

use stdClass;

class ClassWithStdClassProperty
{
    private ?stdClass $stdClassVar = null;

    public function getStdClassVar(): ?stdClass
    {
        return $this->stdClassVar;
    }

    public function setStdClassVar(?stdClass $stdClassVar): void
    {
        $this->stdClassVar = $stdClassVar;
    }
}
