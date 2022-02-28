<?php

namespace Bookboon\JsonLDClient\Mapping;

use Bookboon\JsonLDClient\Client\JsonLDException;

class MappingCollection
{
    /** @var array<MappingEndpoint> */
    protected array $mappings;

    public function __construct(array $mappings)
    {
        foreach ($mappings as $map) {
            if (!($map instanceof MappingEndpoint)) {
                throw new MappingException('Invalid types in collection');
            }
        }

        $this->mappings = $mappings;
    }

    public static function create(array $mappings) : MappingCollection
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
                $map['type'],
                $map['uri'],
                $map['renamed_properties'] ?? [],
                $map['singleton'] ?? false
            );
        }

        return new self($endpoints);
    }

    /**
     * @return MappingEndpoint[]
     */
    public function get() : array
    {
        return $this->mappings;
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
    public function findClassByShortNameOrDefault(string $shortClass, string $defaultNamespace) : string
    {
        if (in_array($shortClass, ['ApiError', 'ApiSource', 'ApiErrorResponse'], true)) {
            return "Bookboon\JsonLDClient\Models\\$shortClass";
        }

        $classFromMapping = null;
        foreach ($this->mappings as $mapping) {
            if ($mapping->matchesShortName($shortClass)) {
                $classFromMapping = $mapping->getType();
            }
        }

        $guessedClassName = $defaultNamespace . '\\' . $shortClass;
        return class_exists($guessedClassName) || $classFromMapping === null ? $guessedClassName : $classFromMapping;
    }
}
