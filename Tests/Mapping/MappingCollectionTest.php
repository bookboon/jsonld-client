<?php

namespace Bookboon\JsonLDClient\Tests\Mapping;

use Bookboon\JsonLDClient\Mapping\MappingCollection;
use Bookboon\JsonLDClient\Mapping\MappingEndpoint;
use Bookboon\JsonLDClient\Mapping\MappingException;
use Bookboon\JsonLDClient\Models\ApiError;
use Bookboon\JsonLDClient\Models\ApiErrorResponse;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\SimpleClass;
use PHPUnit\Framework\TestCase;

class MappingCollectionTest extends TestCase
{
    public function testShortName() : void
    {
        $collection = MappingCollection::create(
            [
                [
                    'type' => 'App\Entity\Test',
                    'uri' => 'http://',
                ]
            ]
        );

        self::assertEquals('App\Entity\Test', $collection->get()[0]->getType());
    }

    public function testFullName() : void
    {
        $collection = MappingCollection::create(
            [
                [
                    'type' => 'OtherApp\Test',
                    'uri' => 'http://',
                ]
            ]
        );

        self::assertEquals('OtherApp\Test', $collection->get()[0]->getType());
    }

    public function testFullNameSsl() : void
    {
        $collection = MappingCollection::create(
            [
                [
                    'type' => 'OtherApp\Test',
                    'uri' => 'https://',
                ]
            ]
        );

        self::assertEquals('OtherApp\Test', $collection->get()[0]->getType());
    }

    public function testInvalidUri() : void
    {
        $this->expectException(MappingException::class);
        $collection = MappingCollection::create(
            [
                [
                    'type' => 'OtherApp\Test',
                    'uri' => '://',
                ]
            ]
        );
    }

    public function testMissingPart() : void
    {
        $this->expectException(MappingException::class);
        $collection = MappingCollection::create(
            [
                [
                    'type' => 'OtherApp\Test'
                ]
            ]
        );
    }

    public function testInvalidConstructor() : void
    {
        $this->expectException(MappingException::class);
        $collection = new MappingCollection(
            [
                new SimpleClass()
            ]
        );
    }

    public function testFindClassByShortNameOrDefault_ApiErrorResponse() : void
    {
        $collection = new MappingCollection([]);
        self::assertEquals(ApiErrorResponse::class, $collection->findClassByShortNameOrDefault('ApiErrorResponse', 'Bookboon\JsonLDClient'));
    }

    public function testFindClassByShortNameOrDefault_ApiError() : void
    {
        $collection = new MappingCollection([]);
        self::assertEquals(ApiError::class, $collection->findClassByShortNameOrDefault('ApiError', 'Bookboon\JsonLDClient'));
    }

    public function testFindClassByShortNameOrDefault_SimpleClass() : void
    {
        $collection = new MappingCollection([
            new MappingEndpoint(SimpleClass::class, 'http://test')
        ]);

        self::assertEquals(SimpleClass::class, $collection->findClassByShortNameOrDefault('SimpleClass', 'Bookboon\JsonLDClient\Tests\Fixtures'));
    }
}
