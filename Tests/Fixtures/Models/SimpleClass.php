<?php

namespace Bookboon\JsonLDClient\Tests\Fixtures\Models;

use Bookboon\JsonLDClient\Attributes\JsonLDEntity;
use Bookboon\JsonLDClient\Attributes\JsonLDProperty;

#[JsonLDEntity(url: '/simple')]
class SimpleClass
{
    #[JsonLDProperty(mappedName: '@MangledValue')]
    protected string $value = '';

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

    public function getId() : string
    {
        return '232d2c41-278a-4377-bd9d-c6046494ceaf';
    }
}
