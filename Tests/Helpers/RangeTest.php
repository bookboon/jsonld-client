<?php

namespace Bookboon\JsonLDClient\Tests\Helpers;

use Bookboon\JsonLDClient\Helpers\Range;
use PHPUnit\Framework\TestCase;

class RangeTest extends TestCase
{
    public function rangeParseProvider() : array
    {
        return [
            ['bytes=0-1023', new Range('bytes', 0, 1023)],
            ['bytes=10-', new Range('bytes', 10, 0)]
        ];
    }

    /**
     * @dataProvider rangeParseProvider
     */
    public function testRangeCanParse(string $rangeHeaderVal, Range $expectedRangeHeader) : void
    {
        $range = new Range;
        $range->parse($rangeHeaderVal);

        $this->assertEquals($expectedRangeHeader, $range);
    }

    public function testRangeUnitDefaultsToBytesWhenEmpty() : void
    {
        $range = new Range();

        $this->assertStringStartsWith("bytes", $range->format());
    }

}
