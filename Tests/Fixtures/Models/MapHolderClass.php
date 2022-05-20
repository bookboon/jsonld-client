<?php

namespace Bookboon\JsonLDClient\Tests\Fixtures\Models;

use ArrayObject;

class MapHolderClass
{
    /** @var ArrayObject<string|int, MapValue>|null $map */
    protected ?ArrayObject $map = null;

    /**
     * @param ArrayObject<string|int, MapValue>|null $map
     */
    public function setMap(?ArrayObject $map): void
    {
        $this->map = $map;
    }

    /**
     * @return ArrayObject<string|int, MapValue>|null
     */
    public function getMap(): ?ArrayObject
    {
        return $this->map;
    }
}
