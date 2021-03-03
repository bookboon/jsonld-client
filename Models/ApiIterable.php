<?php

namespace Bookboon\JsonLDClient\Models;

use ArrayAccess;
use Bookboon\JsonLDClient\Helpers\LinkParser;
use Countable;
use Iterator;
use Psr\Http\Message\ResponseInterface;

class ApiIterable implements ArrayAccess, Iterator, Countable
{
    public const OFFSET = 'offset';
    public const LIMIT = 'limit';

    public const LINK_HEADER = 'Link';

    /** @var callable(array): ResponseInterface */
    private $_makeRequest;

    /** @var callable callable(string): (Array<object>|object) */
    private $_deserialize;
    private array $_params;

    private int $requestLimit = 10;

    private int $position = 0;
    /*
     * Contains batches, e.g. with requestLimit = 10 that would get [0 => [...], 10 => [...]]
     */
    private array $results = [];
    private bool $isIterationDisabled = false;
    private bool $hasRequested = false;
    private ?LinkParser $link = null;

    /**
     * ApiIterable constructor.
     * @param callable(array): ResponseInterface $makeRequest
     * @param callable(string): (Array<object>|object) $deserialize
     * @param Array<string,string|int> $params
     */
    public function __construct(callable $makeRequest, callable $deserialize, array $params)
    {
        $this->_makeRequest = $makeRequest;
        $this->_deserialize = $deserialize;
        $this->_params = $params;
        if (isset($this->_params[self::LIMIT])) {
            $this->requestLimit = (int) $this->_params[self::LIMIT];
        }

        if (array_key_exists(self::OFFSET, $this->_params)) {
            $this->isIterationDisabled = true;
        }
    }

    protected function makeRequest(int $offset) : void
    {
        $makeRequest = $this->_makeRequest;
        $deserialize = $this->_deserialize;
        $queryOffset = $this->_params[self::OFFSET] ?? $offset;

        /** @var ResponseInterface $response */
        $response = $makeRequest(
            array_merge($this->_params, [self::OFFSET => $queryOffset, self::LIMIT => $this->requestLimit])
        );

        $link = $response->getHeader(static::LINK_HEADER);
        if (false === $this->hasRequested && isset($link[0]) && is_string($link[0])) {
            $this->link = new LinkParser($link[0]);
        }

        $this->hasRequested = true;
        $jsonContents = $response->getBody()->getContents();

        if ($jsonContents !== '[]' && $jsonContents !== "[]\n") {
            $this->results[$offset] = $deserialize($jsonContents);
        }
    }

    protected function locate(int $offset) : ?object
    {
        $batchKey = ((int) floor($offset / $this->requestLimit)) * $this->requestLimit;

        if (isset($this->results[$batchKey], $this->results[$batchKey][$offset % $this->requestLimit])) {
            return $this->results[$batchKey][$offset % $this->requestLimit];
        }

        if (!$this->hasRequested ||
            ($this->link !== null && !array_key_exists($batchKey, $this->results) && !$this->isIterationDisabled)
        ) {
            $this->makeRequest($batchKey);
            return $this->locate($offset);
        }

        return null;
    }

    public function current()
    {
        return $this->locate($this->position);
    }

    public function next()
    {
        ++$this->position;
    }

    public function key()
    {
        return $this->position;
    }

    /**
     * Return valid if entity exists or tries to query if out of batch.
     * If null is encountered within a batch then end loop by returning false
     *
     * @return boolean
     */
    public function valid()
    {
        return $this->locate($this->position) !== null;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function offsetExists($offset)
    {
        if (is_int($offset)) {
            return $this->locate($this->position) !== null;
        }

        return false;
    }

    public function offsetGet($offset)
    {
        if (is_int($offset)) {
            return $this->locate($offset);
        }

        return null;
    }

    public function offsetSet($offset, $value)
    {
        // Not implemented by design
    }

    public function offsetUnset($offset)
    {
        // Not implemented by design
    }

    /**
     * Returns count of already fetched items, not all remote
     *
     * @return integer
     */
    public function count()
    {
        if (false === $this->hasRequested) {
            $this->makeRequest(0);
        }

        $count = 0;

        if ($this->link !== null) {
            $count = $this->link->offset(LinkParser::LAST) ?? 0;
        }

        if ($count === 0) {
            foreach ($this->results as $result) {
                $count += count($result);
            }
        }

        return $count;
    }
}