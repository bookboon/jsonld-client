<?php

namespace Bookboon\JsonLDClient\Mapping;

use Bookboon\JsonLDClient\Client\JsonLDException;

class MappingEndpoint
{
    protected string $type;
    protected string $uri;

    public function __construct(string $type, string $uri)
    {
        if (strpos($type, "\\") === false) {
            throw new MappingException('Must use full className');
        }

        $this->type = $type;
        $this->uri = $uri;
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

    protected function endsWith(string $haystack, string $needle): bool
    {
        return substr($haystack, -strlen($needle)) === $needle;
    }
}
