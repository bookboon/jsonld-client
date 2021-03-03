<?php

namespace Bookboon\JsonLDClient\Tests\Helpers;

use Bookboon\JsonLDClient\Helpers\LinkParser;
use PHPUnit\Framework\TestCase;

class LinkParserTest extends TestCase
{
    public function testParseLinkRansdom() : void
    {
        $link = 'test,t,s,s,s,,';
        $parser = new LinkParser($link);
        self::assertNull($parser->offset(LinkParser::LAST));
        self::assertNull($parser->limit(LinkParser::LAST));
    }

    public function testParseLinkBroken() : void
    {
        $link = '<http://publishing.local.bookboon.io/api/v1/books?type=Academic&type=Academicoffset=1800&limit=100>; rel="last",<http://publishing.local.bookboon.io/api/v1/books?type=Academic&type=Academicoffset=100&limit=100>; rel="next",<http://publishing.local.bookboon.io/api/v1/books?type=Academic&type=Academicoffset=0&limit=100>; rel="first"';
        $parser = new LinkParser($link);
        self::assertNull($parser->offset(LinkParser::LAST));
        self::assertEquals(100, $parser->limit(LinkParser::LAST));
    }

    public function testParseLInkGood() : void
    {
        $link = '<http://publishing.local.bookboon.io/api/v1/books?type=Academic&offset=0&limit=100>; rel="first",<http://publishing.local.bookboon.io/api/v1/books?type=Academic&offset=1800&limit=100>; rel="last",<http://publishing.local.bookboon.io/api/v1/books?type=Academic&offset=100&limit=100>; rel="next"';
        $parser = new LinkParser($link);
        self::assertEquals(1800, $parser->offset(LinkParser::LAST));
        self::assertEquals(100, $parser->limit(LinkParser::LAST));
    }
}
