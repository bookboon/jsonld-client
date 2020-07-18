<?php

namespace Bookboon\JsonLDClient\Mapping;

use Bookboon\JsonLDClient\Client\JsonLDException;

class MappingEndpoint
{
    protected $type;
    protected $uri;

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
    public function matches(string $className) : bool
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

    public function matchesShortName(string $shortClass) : bool
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
        if (preg_match('/\{([A-z0-9_-]+)\}/', $url, $matches)) {
            for ($i = 0, $iMax = count($matches); $iMax > $i; $i++) {
                if ($i % 2 === 0) {
                    continue;
                }

                if (!isset($params[$matches[$i]])) {
                    throw new JsonLDException('Missing param: ' . $matches[$i]);
                }

                $url = str_replace($matches[$i - 1], $params[$matches[$i]], $url);
                unset($params[$matches[$i]]);
            }
        }

        return $url;
    }

    protected function endsWith(string $haystack, string $needle) : bool
    {
        return substr($haystack, -strlen($needle)) === $needle;
    }
}
