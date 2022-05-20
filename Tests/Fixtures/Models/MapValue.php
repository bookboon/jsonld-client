<?php

namespace Bookboon\JsonLDClient\Tests\Fixtures\Models;

use ArrayObject;

class MapValue
{
    /** @var ArrayObject<string|int, mixed>|null $rules */
    protected ?ArrayObject $rules = null;

    /**
     * @param ArrayObject<string|int, mixed>|null $rules
     */
    public function setRules(?ArrayObject $rules): void
    {
        $this->rules = $rules;
    }

    /**
     * @return ArrayObject<string|int, mixed>|null
     */
    public function getRules(): ?ArrayObject
    {
        return $this->rules;
    }
}
