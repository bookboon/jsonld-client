<?php

namespace Bookboon\JsonLDClient\Tests\Models;

use Bookboon\JsonLDClient\Helpers\LinkParser;
use Bookboon\JsonLDClient\Models\ApiIterable;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ApiIterableTest extends TestCase
{
    public function testEmpty() : void
    {
        $calledCount = 0;

        $array = new ApiIterable(
            function (array $params) use (&$calledCount) {
                $calledCount += 1;
                return new Response();
            },
            fn(string $data) => [],
            []
        );


        self::assertCount(0, $array);
        self::assertCount(0, $array);

        self::assertEquals(1, $calledCount);
    }

    public function testHasResultsLt10() : void
    {
        $calledCount = 0;

        $array = new ApiIterable(
            function (array $params) use (&$calledCount) {
                $calledCount += 1;
                return new Response();
            },
            fn(string $data) => $this->generateLetters('a', 'j'),
            []
        );


        self::assertCount(10, $array);
        self::assertCount(10, $array);

        self::assertEquals(1, $calledCount);
    }

    public function testHasResultsGt10() : void
    {
        $calledCount = 0;

        $array = new ApiIterable(
            function (array $params) use (&$calledCount) {
                $calledCount += 1;
                return new Response(200, [ApiIterable::LINK_HEADER => ['Test']]);
            },
            function (string $data) use (&$calledCount) {
                return $calledCount == 1 ? $this->generateLetters('a', 'j'): $this->generateLetters('k', 'm');
            },
            []
        );

        $items = [];
        foreach ($array as $item) {
            $items[] = $item;
        }
        self::assertCount(13, $array);
        self::assertEquals(2, $calledCount);
    }

    public function testHasResultsGt20() : void
    {
        $calledCount = 0;

        $array = new ApiIterable(
            function (array $params) use (&$calledCount) {
                $calledCount += 1;
                return new Response(200, [ApiIterable::LINK_HEADER => ['Test']]);
            },
            function (string $data) use (&$calledCount) {
                switch ($calledCount) {
                    case 1:
                        return $this->generateLetters('a', 'j');
                    case 2:
                        return $this->generateLetters('k', 't');
                    case 3:
                        return $this->generateLetters('u', 'z');
                }
            },
            []
        );

        $items = [];
        foreach ($array as $item) {
            $items[] = $item;
        }
        self::assertCount(26, $array);
        self::assertEquals(3, $calledCount);
    }

    public function testHasResultsExactly30() : void
    {
        $calledCount = 0;
        $link = '<http://test.com/entity?offset=0&limit=100>; rel="first",<http://test.com/entity?offset=1800&limit=100>; rel="last",<http://test.com/entity?offset=100&limit=100>; rel="next"';

        $array = new ApiIterable(
            function (array $params) use (&$calledCount, $link) {
                $calledCount += 1;
                self::assertEquals(10, $params[ApiIterable::LIMIT]);

                return new Response(
                    200,
                    [ApiIterable::LINK_HEADER => [$link]],
                    $calledCount > 3 ? '[]' : '[{"not_used":"in_this_test"}]'
                );
            },
            function (string $data) use (&$calledCount) {
                if ($calledCount < 4) {
                    return $this->generateLetters('a', 'j');
                }
                return [];
            },
            []
        );

        $items = [];
        foreach ($array as $item) {
            $items[] = $item;
        }

        self::assertCount(30, $items);
        self::assertEquals(4, $calledCount);
    }

    public function testGetSpecificOffset() : void
    {
        $calledCount = 0;

        $array = new ApiIterable(
            function (array $params) use (&$calledCount) {
                $calledCount += 1;
                self::assertEquals(10, $params[ApiIterable::LIMIT]);
                self::assertEquals(20, $params[ApiIterable::OFFSET]);
                return new Response(200, [ApiIterable::LINK_HEADER => ['Test']]);
            },
            function (string $data) {
                return $this->generateLetters('k', 'm');
            },
            [ApiIterable::OFFSET => 20]
        );

        $items = [];
        foreach ($array as $item) {
            $items[] = $item;
        }

        self::assertCount(3, $array);
        self::assertEquals(1, $calledCount);
    }

    public function testGetSpecificOffsetZero() : void
    {
        $calledCount = 0;

        $array = new ApiIterable(
            function (array $params) use (&$calledCount) {
                $calledCount += 1;
                self::assertEquals(10, $params[ApiIterable::LIMIT]);
                self::assertEquals(0, $params[ApiIterable::OFFSET]);
                return new Response(200, [ApiIterable::LINK_HEADER => ['Test']]);
            },
            function (string $data) {
                return $this->generateLetters('k', 'm');
            },
            [ApiIterable::OFFSET => 0]
        );

        $items = [];
        foreach ($array as $item) {
            $items[] = $item;
        }

        self::assertCount(3, $array);
        self::assertEquals(1, $calledCount);
    }

    public function testGetCountFromRemoteCollection() : void
    {
        $calledCount = 0;
        $link = '<http://test.com/entity?offset=0&limit=10>; rel="first",<http://test.com/entity?offset=50&limit=10>; rel="last",<http://test.com/entity?offset=10&limit=10>; rel="next"';

        $array = new ApiIterable(
            function (array $params) use (&$calledCount, $link) {
                $calledCount += 1;
                self::assertEquals(10, $params[ApiIterable::LIMIT]);
                self::assertEquals(0, $params[ApiIterable::OFFSET]);
                return new Response(200, [ApiIterable::LINK_HEADER => [$link]]);
            },
            function (string $data) {
                return $this->generateLetters('a', 'j');
            },
            []
        );

        $test = $array[5];

        self::assertCount(60, $array);
        self::assertEquals(1, $calledCount);
    }

    public function testGetExactCountFromRemoteCollection() : void
    {
        $calledCount = 0;
        $link = '<http://test.com/entity?offset=0&limit=10>; rel="first",<http://test.com/entity?offset=50&limit=10>; rel="last",<http://test.com/entity?offset=10&limit=10>; rel="next"';

        $array = new ApiIterable(
            function (array $params) use (&$calledCount, $link) {
                self::assertEquals(10, $params[ApiIterable::LIMIT]);
                self::assertEquals($calledCount * 10, $params[ApiIterable::OFFSET]);
                $calledCount += 1;
                return new Response(200, [ApiIterable::LINK_HEADER => [$link]]);
            },
            function (string $data) use (&$calledCount) {
                if ($calledCount <= 5) {
                    return $this->generateLetters('a', 'j');
                }

                return $this->generateLetters('k', 'n');
            },
            []
        );

        $items = [];
        foreach ($array as $item) {
            $items[] = $item;
        }

        self::assertCount(54, $array);
        self::assertEquals(6, $calledCount);
    }

    private function generateLetters(string $start, string $end) : array
    {
        return array_map(
            function ($item) {
                $std = new \stdClass();
                $std->letter = $item;
                return $std;
            },
            range($start, $end)
        );
    }
}
