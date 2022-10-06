<?php

namespace Bookboon\JsonLDClient\Tests\Helpers;

use Bookboon\JsonLDClient\Helpers\ContentRange;
use PHPUnit\Framework\TestCase;

class ContentRangeTest extends TestCase
{

    public function parseProviders() : array
    {
        return [
            ['bytes 0-10/100', new ContentRange('bytes', 100, 0, 10)],
            ['bytes */100', new ContentRange('bytes', 100, 0, 99)]
        ];
    }

    public function formatProviders() : array
    {
        return [
            ['bytes 0-10/100', new ContentRange('', 100, 0, 10)],
            ['bytes 10-20/*', new ContentRange('', -1, 10, 20)],
            ['bytes */0', new ContentRange('', 0, 0, 10)],
            ['bytes */100', new ContentRange('', 100, 0, 0)],
            ['random 0-10/100', new ContentRange('random', 100, 0, 10)]
        ];
    }

    /**
     * @dataProvider parseProviders
     */
    public function testParse(string $headerValue, ContentRange $expected) : void
    {
        $contentRange = new ContentRange("", 0, 0, 0);
        $contentRange->parse($headerValue);

        $this->assertEquals($expected, $contentRange);
    }

    /**
     * @dataProvider formatProviders
     */
    public function testFormat(string $expected, ContentRange $contentRange) : void
    {
        $this->assertEquals($expected, $contentRange->format());
    }

}
