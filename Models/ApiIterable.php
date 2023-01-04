<?php

namespace Bookboon\JsonLDClient\Models;

use ArrayAccess;
use Bookboon\JsonLDClient\Helpers\ContentRange;
use Bookboon\JsonLDClient\Helpers\LinkParser;
use Bookboon\JsonLDClient\Helpers\Range;
use Countable;
use Iterator;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ApiIterable
 * @package Bookboon\JsonLDClient\Models
 * @template T
 * @template-implements ArrayAccess<int,T>
 * @template-implements Iterator<int,T>
 */
class ApiIterable implements ArrayAccess, Iterator, Countable
{
    public const OFFSET = 'offset';
    public const LIMIT = 'limit';

    public const LINK_HEADER = 'Link';
    public const CONTENT_RANGE_HEADER = 'Content-Range';
    public const RANGE_HEADER = 'Range';

    /** @var callable(array, array): ResponseInterface */
    private $_makeRequest;

    /** @var callable callable(string): (Array<T>|T) */
    private $_deserialize;
    private array $_params;

    private int $requestLimit = 10;

    private int $position = 0;
    /**
     * Contains batches, e.g. with requestLimit = 10 that would get [0 => [...], 10 => [...]]
     * @var array<int, array<int,T>>
     */
    private array $results = [];
    private bool $isIterationDisabled = false;
    private bool $hasRequested = false;
    private ?LinkParser $link = null;
    private ?ContentRange $contentRange = null;
    private string $_unitName;
    private bool $isPaginationSupported = false;

    /**
     * ApiIterable constructor.
     * @param callable(array, array): ResponseInterface $makeRequest
     * @param callable(string): (array<T>|T) $deserialize
     * @param Array<string,string|int> $params
     */
    public function __construct(callable $makeRequest, callable $deserialize, array $params, string $unitName)
    {
        $this->_makeRequest = $makeRequest;
        $this->_deserialize = $deserialize;
        $this->_params = $params;
        $this->_unitName = $unitName;

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

        $rangeHeader = new Range($this->_unitName, $queryOffset, $this->requestLimit);

        /** @var ResponseInterface $response */
        $response = $makeRequest(
            array_merge($this->_params, [self::OFFSET => $queryOffset, self::LIMIT => $this->requestLimit]),
            [self::RANGE_HEADER => $rangeHeader->format()]
        );

        $link = $response->getHeader(static::LINK_HEADER);
        if (false === $this->hasRequested && isset($link[0]) && is_string($link[0])) {
            $this->isPaginationSupported = true;
            $this->link = new LinkParser($link[0]);
        }

        $contentRangeHeaderVal = $response->getHeader(static::CONTENT_RANGE_HEADER);
        if (false === $this->hasRequested && isset($contentRangeHeaderVal[0]) && is_string($contentRangeHeaderVal[0])) {
            $this->link = null;
            $this->isPaginationSupported = true;
            $this->contentRange = new ContentRange;
            $this->contentRange->parse($contentRangeHeaderVal[0]);
        }

        $this->hasRequested = true;
        $jsonContents = $response->getBody()->getContents();

        if ($jsonContents !== '[]' && $jsonContents !== "[]\n") {
            $this->results[$offset] = $deserialize($jsonContents);
            return;
        }

        // Prevent forever loop
        $this->results[$offset] = [];
    }

    /**
     * @param integer $offset
     * @psalm-return T|null
     * @return object|null
     */
    protected function locate(int $offset)
    {
        $batchKey = 0;
        $batchOffset = $offset;

        if ($this->isPaginationSupported) {
            $batchKey = ((int) floor($offset / $this->requestLimit)) * $this->requestLimit;
            $batchOffset = $offset % $this->requestLimit;
        }

        if (isset($this->results[$batchKey], $this->results[$batchKey][$batchOffset])) {
            return $this->results[$batchKey][$batchOffset];
        }

        if (!$this->hasRequested ||
            (($this->link !== null || $this->contentRange !== null) && !array_key_exists($batchKey, $this->results) && !$this->isIterationDisabled)
        ) {
            $this->makeRequest($batchKey);
            return $this->locate($offset);
        }

        return null;
    }

    /**
     * Return the current element
     * @link https://php.net/manual/en/iterator.current.php
     * @return T Can return any type.
     */
    public function current(): mixed
    {
        return $this->locate($this->position);
    }

    /**
     * {@inheritDoc}
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * {@inheritDoc}
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * {@inheritDoc}
     */
    public function valid(): bool
    {
        return $this->locate($this->position) !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     *
     * @psalm-param int $offset
     */
    public function offsetExists($offset): bool
    {
        return $this->locate($this->position) !== null;
    }

    /**
     * {@inheritDoc}
     * @return T
     * @psalm-param int $offset
     */
    public function offsetGet($offset): mixed
    {
        return $this->locate($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value): void
    {
        // Not implemented by design
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset): void
    {
        // Not implemented by design
    }

    /**
     * {@inheritDoc}
     *
     * Returns count of already fetched items or if set the estimated total
     * count based on the 'last' element of the link header
     *
     * @return int
     */
    public function count(): int
    {
        if (false === $this->hasRequested) {
            $this->makeRequest(0);
        }

        $count = 0;

        if ($this->link !== null) {
            $last = $this->link->offset(LinkParser::LAST);
            if ($last !== null) {
                // The last page will be hidden unless we add the offset to count. For 21 results the last page will be
                // ?limit=10&offset=20, which would make $last = 20. In the case we've loaded the entire collection,
                // return the accurate count of the last page.
                $lastPageCount = isset($this->results[$last]) ? count($this->results[$last]) : $this->requestLimit;
                $count = $last + $lastPageCount;
            }
        }

        if ($this->contentRange !== null) {
            $count = $this->contentRange->getSize();
        }

        if ($count === 0) {
            foreach ($this->results as $result) {
                $count += count($result);
            }
        }

        return $count;
    }
}
