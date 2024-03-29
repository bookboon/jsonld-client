<?php

namespace Bookboon\JsonLDClient\Mapping;

use Bookboon\JsonLDClient\Client\JsonLDException;

class MappingEndpoint
{
    protected string $type;
    protected string $uri;
    protected array $propertyMap;
    protected array $reversePropertyMap;
    protected bool $singleton;

    public function __construct(string $type, string $uri, array $propertyMap = [], bool $singleton = false)
    {
        if (strpos($type, "\\") === false) {
            throw new MappingException('Must use full className');
        }

        $this->type = $type;
        $this->uri = $uri;
        $this->propertyMap = $propertyMap;
        $this->reversePropertyMap = array_flip($propertyMap);
        $this->singleton = $singleton;
    }

    /**
     * @param string $className
     * @return boolean
     */
    public function matches(string $className): bool
    {
        if ($this->getType() === $className) {
            return true;
        }

        if (strpos($this->getType(), '*') !== false) {
            $testClassNameParts = explode('\\', $className);
            $testShortName = array_pop($testClassNameParts);

            $expectClassNameParts = explode('\\', $this->getType());
            $expectShortName = array_pop($expectClassNameParts);

            return $testClassNameParts === $expectClassNameParts &&
                $this->endsWith($testShortName, ltrim($expectShortName, '*'));
        }

        return false;
    }

    public function matchesShortName(string $shortClass): bool
    {
        $expectClassNameParts = explode('\\', $this->getType());
        $expectShortName = array_pop($expectClassNameParts);

        return $expectShortName === $shortClass;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param array $params
     * @return string
     * @throws JsonLDException
     */
    public function getUrl(array $params = []): string
    {
        $url = rtrim($this->uri, '/');
        $matches = [];
        if (preg_match_all('/\{([A-z0-9_-]+)\}/', $url, $matches)) {
            foreach ($matches[0] as $key => $singleMatch) {
                if (!isset($matches[1][$key]) || !isset($params[$matches[1][$key]])) {
                    throw new JsonLDException('Missing param: ' . $singleMatch);
                }

                $url = str_replace($singleMatch, $params[$matches[1][$key]], $url);
            }
        }

        return $url;
    }

    public function normaliseData(array $data): array
    {
        return self::applyMapping($data, $this->propertyMap);
    }

    public function denormaliseData(array $data): array
    {
        return self::applyMapping($data, $this->reversePropertyMap);
    }

    protected static function applyMapping(array $data, array $mapping): array
    {
        foreach ($mapping as $srcKey => $dstKey) {
            if (isset($data[$srcKey])) {
                $data[$dstKey] = $data[$srcKey];
                unset($data[$srcKey]);
            }
        }

        return $data;
    }

    public function getClassNamespace() : string
    {
        if ($pos = strrpos($this->type, '\\')) {
            return substr($this->type, 0, $pos);
        }

        return '';
    }

    public function isSingleton() : bool
    {
        return $this->singleton;
    }

    public function getUnitName(): string
    {
        $pos = strrpos($this->uri, '/');
        return !$pos ? $this->uri : substr($this->uri, $pos + 1);
    }

    protected function endsWith(string $haystack, string $needle): bool
    {
        return substr($haystack, -strlen($needle)) === $needle;
    }
}
