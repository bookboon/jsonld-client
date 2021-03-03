<?php

namespace Bookboon\JsonLDClient\Helpers;

use Bookboon\JsonLDClient\Models\ApiIterable;

class LinkParser
{
    public const LAST = 'last';
    public const PREVIOUS = 'prev';
    public const NEXT = 'next';
    public const FIRST = 'first';

    protected string $linkStr;
    protected array $linkArr = [];
    protected bool $isParsed = false;

    public function __construct(string $header)
    {
        $this->linkStr = $header;
    }

    public function parse() : void
    {
        if (false === $this->isParsed) {
            foreach (explode(',', $this->linkStr) as $str) {
                $text = $this->getLinkText($str);
                $this->linkArr[$text] = $this->getParameters($str);
            }
        }
    }

    public function offset(string $key) : ?int
    {
        $this->parse();
        return $this->linkArr[$key][ApiIterable::OFFSET] ?? null;
    }

    public function limit(string $key) : ?int
    {
        $this->parse();
        return $this->linkArr[$key][ApiIterable::LIMIT] ?? null;
    }

    protected function getLinkText(string $content) : string
    {
        if (false !== $pos = strpos($content, "rel=")) {
            $text = substr($content, $pos + 4);

            return trim($text, "\"");
        }

        return '';
    }

    protected function getParameters(string $content) : array
    {
        $result = [];
        $parameters = [ApiIterable::LIMIT, ApiIterable::OFFSET];

        $parsedUrl = parse_url($content, PHP_URL_QUERY);
        parse_str($parsedUrl, $queryParams);

        foreach ($parameters as $param) {
            if (array_key_exists($param, $queryParams)) {
                $value = preg_replace('/[^0-9]+/', '', $queryParams[$param]);
                $result[$param] = intval($value, 10);
            }
        }

        return $result;
    }
}
