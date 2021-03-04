<?php

namespace Bookboon\JsonLDClient\Mapping;

use Bookboon\JsonLDClient\Client\JsonLDException;

class MappingCollection
{
    protected string  $defaultNamespace;

    /** @var array<MappingEndpoint> */
    protected array $mappings;

    public function __construct(array $mappings, string $defaultNamespace = 'App\\Entity')
    {
        foreach ($mappings as $map) {
            if (!($map instanceof MappingEndpoint)) {
                throw new MappingException('Invalid types in collection');
            }
        }

        $this->defaultNamespace = rtrim($defaultNamespace, '\\');
        $this->mappings = $mappings;
    }

    public static function create(array $mappings, string $defaultNamespace = 'App\\Entity') : MappingCollection
    {
        $endpoints = [];

        foreach ($mappings as $map) {
            if (!isset($map['uri'], $map['type'])) {
                throw new MappingException('Invalid collection');
            }

            if (!preg_match('/^https?:\/\//', $map['uri'])) {
                throw new MappingException('Invalid uri');
            }

            $endpoints[] = new MappingEndpoint(
                strpos($map['type'], "\\") === false ? "$defaultNamespace\\{$map['type']}" : $map['type'],
                $map['uri']
            );
        }
        return new self($endpoints, $defaultNamespace);
    }

    /**
     * @return MappingEndpoint[]
     */
    public function get() : array
    {
        return $this->mappings;
    }

    /**
     * @return string
     */
    public function getDefaultNamespace(): string
    {
        return $this->defaultNamespace;
    }

    public function findEndpointByClass(string $className) : MappingEndpoint
    {
        foreach ($this->mappings as $map) {
            if ($map->matches($className)) {
                return $map;
            }
        }

        throw new JsonLDException('Not mapping for class: ' . $className);
    }

    /**
     * @param string $shortClass
     * @return string
     */
    public function findClassByShortNameOrDefault(string $shortClass) : string
    {
        if (in_array($shortClass, ['ApiError', 'ApiErrorResponse'], true)) {
            return "Bookboon\JsonLDClient\Models\\$shortClass";
        }

        foreach ($this->mappings as $mapping) {
            if ($mapping->matchesShortName($shortClass)) {
                return $mapping->getType();
            }
        }

        return $this->getDefaultNamespace() . '\\' . $shortClass;
    }
}
