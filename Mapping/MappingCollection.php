<?php

namespace Bookboon\JsonLDClient\Mapping;

use Bookboon\JsonLDClient\Attributes\JsonLDEntity;
use Bookboon\JsonLDClient\Attributes\JsonLDProperty;
use Bookboon\JsonLDClient\Client\JsonLDException;
use Psr\SimpleCache\CacheInterface;

class MappingCollection
{
    protected const CACHE_PREFIX = 'api-mapping-';
    /** @var array<MappingEndpoint> */
    protected array $mappings;
    /** @var array<string,string> */
    private array $apis;
    private ?CacheInterface $cache;

    /**
     * @param array $mappings
     * @param array<string,string> $apis
     * @throws MappingException
     */
    public function __construct(array $mappings, array $apis, CacheInterface $cache = null)
    {
        foreach ($mappings as $map) {
            if (!($map instanceof MappingEndpoint)) {
                throw new MappingException('Invalid types in collection');
            }
        }

        $this->mappings = $mappings;
        $this->apis = $apis;
        $this->cache = $cache;
    }

    public static function create(array $mappings, array $apis): MappingCollection
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

        return new self($endpoints, $apis);
    }

    /**
     * @return MappingEndpoint[]
     */
    public function get(): array
    {
        return $this->mappings;
    }

    public function findEndpointByClass(string $className): MappingEndpoint
    {
        foreach ($this->mappings as $map) {
            if ($map->matches($className)) {
                return $map;
            }
        }

        $mapping = $this->makeEndpointForClass($className);
        $this->mappings[] = $mapping;
        return $mapping;
    }

    /**
     * @param string $shortClass
     * @return string
     */
    public function findClassByShortNameOrDefault(string $shortClass, string $defaultNamespace): string
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

    /**
     * @throws JsonLDException
     */
    protected function makeEndpointForClass(string $className): MappingEndpoint
    {
        $properties = null;

        if ($this->cache) {
            $properties = $this->cache->get(self::CACHE_PREFIX . $className);
        }

        if (!$properties) {
            $properties = $this->getEndpointProperties($className);
        }

        if ($this->cache) {
            $this->cache->set(self::CACHE_PREFIX . $className, $properties);
        }

        [$className, $url, $mapping, $isSingleton] = $properties;
        return new MappingEndpoint($className, $url, $mapping, $isSingleton);
    }

    protected function getEndpointProperties(string $className): array {
        if (!class_exists($className)) {
            throw new JsonLDException('No mapping for class: ' . $className);
        }

        $reflClass = new \ReflectionClass($className);
        $attrs = $reflClass->getAttributes(JsonLDEntity::class);
        if (!count($attrs)) {
            throw new JsonLDException('No mapping for class: ' . $className);
        }

        /** @var JsonLDEntity $entity */
        $entity = $attrs[0]->newInstance();
        $url = $entity->getUrl();

        if (strlen($url) && $url[0] == '/') {
            $apiRoot = $this->findApiForClass($className);
            if ($apiRoot === '') {
                throw new MappingException("No base api URL found for class $className");
            }

            $url = $apiRoot . $url;
        }

        $mapping = [];

        foreach ($reflClass->getProperties() as $reflProp) {
            $propAttrs = $reflProp->getAttributes(JsonLDProperty::class);
            if (count($propAttrs)) {
                /** @var JsonLDProperty $prop */
                $prop = $propAttrs[0]->newInstance();
                $mapping[$reflProp->getName()] = $prop->getMappedName();
            }
        }

        return [$className, $url, $mapping, $entity->isSingleton()];
    }

    protected function findApiForClass(string $classname): string {
        foreach ($this->apis as $namespace => $apiUrl) {
            if (str_starts_with($classname, $namespace)) {
                return $apiUrl;
            }
        }

        return '';
    }
}
