<?php

namespace Bookboon\JsonLDClient\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class JsonLDProperty
{
    private string $mappedName;

    public function __construct(string $mappedName)
    {
        $this->mappedName = $mappedName;
    }

    /**
     * @return string
     */
    public function getMappedName(): string
    {
        return $this->mappedName;
    }

    /**
     * @param string $mappedName
     */
    public function setMappedName(string $mappedName): void
    {
        $this->mappedName = $mappedName;
    }
}
