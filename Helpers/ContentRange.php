<?php

namespace Bookboon\JsonLDClient\Helpers;

use RuntimeException;

class ContentRange
{
    private const REGEX = '/^(\w+)\s+(?:(\d+)-(\d+)|(\*))\/(\d+|\*)$/';

    public function __construct(
        private string $unit = 'bytes',
        private int $size = 0,
        private int $start = 0,
        private int $end = 0
    )
    {
        if ($this->unit === '') {
            $this->unit = 'bytes';
        }
    }

    public function format() : string
    {
        if ($this->size == 0 || $this->end == 0) {
            return sprintf("%s */%d", $this->unit, $this->size);
        }

        if ($this->size == -1) {
            return sprintf("%s %d-%d/*", $this->unit, $this->start, $this->end);
        }

        return sprintf("%s %d-%d/%d", $this->unit, $this->start, $this->end, $this->size);
    }

    public function parse(string $contentRangeHeader) : void
    {
        $matches = [];

        preg_match(self::REGEX, $contentRangeHeader, $matches);

        if (empty($matches)) {
            throw new RuntimeException("cannot parse content range header");
        }

        $this->unit = $matches[1];

        if ($matches[5] === '*') {
            if ($matches[4] === '*' ) {
                throw new RuntimeException("no size or range");
            }

            throw new RuntimeException("no content range size");
        }

        $this->size = (int) ($matches[5] ?? 0);
        $this->start = (int) ($matches[2] ?? 0);
        $this->end = (int) ($matches[3] ?? 0);

        if ($matches[4] === '*') {
            $this->end = $this->size - 1; // subtract 1 as range is inclusive
        }
    }

    public function getSize() : int
    {
        return $this->size;
    }
}
