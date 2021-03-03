<?php

namespace Bookboon\JsonLDClient\Tests\Models;

use Bookboon\JsonLDClient\Models\ApiIterable;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ApiIterableTest extends TestCase
{
    public function testEmpty()
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

    public function testHasResultsLt10()
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

    public function testHasResultsGt10()
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

    public function testHasResultsGt20()
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

    public function testGetSpecificOffset()
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
