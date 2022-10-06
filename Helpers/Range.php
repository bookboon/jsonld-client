<?php

namespace Bookboon\JsonLDClient\Helpers;

class Range
{
    private const REGEX = '/^(\w+)=(\d+)-(\d+)?$/';

    public function __construct(
        private string $unit = 'bytes',
        private int    $start = 0,
        private int    $end = 0
    )
    {
    }

    public function format() : string
    {
        return sprintf("%s=%d-%d", $this->unit, $this->start, $this->end);
    }

    public function parse(string $rangeHeaderVal) : void
    {
        $matches = [];
        preg_match(self::REGEX, $rangeHeaderVal, $matches);

        if (empty($matches)) {
            throw new \RuntimeException("invalid $rangeHeaderVal");
        }

        $this->unit = $matches[1] ?? '';
        $this->start = (int) ($matches[2] ?? 0);
        $this->end =  (int) ($matches[3] ?? 0);
    }

    public function getStart() : int
    {
        return $this->start;
    }

    public function getEnd() :int
    {
        return $this->end;
    }
}
