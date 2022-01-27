<?php

namespace Bookboon\JsonLDClient\Tests\Mapping;

use Bookboon\JsonLDClient\Client\JsonLDException;
use Bookboon\JsonLDClient\Mapping\MappingEndpoint;
use PHPUnit\Framework\TestCase;

class MappingEndpointTest extends TestCase
{
    public function testMatchesPlainTrue() : void
    {
        $endpoint = new MappingEndpoint("App\\Entity\\Test", 'http://test');
        self::assertTrue($endpoint->matches("App\\Entity\\Test"));
    }

    public function testMatchesPlainFalse() : void
    {
        $endpoint = new MappingEndpoint("App\\Entity\\OtherClass", 'http://test');
        self::assertFalse($endpoint->matches("App\\Entity\\Test"));
    }

    public function testMatchesWildcardTrue() : void
    {
        $endpoint = new MappingEndpoint("App\\Entity\\*Class", 'http://test');
        self::assertTrue($endpoint->matches("App\\Entity\\TestClass"));
    }

    public function testMatchesWildcardFalseBadSuffix() : void
    {
        $endpoint = new MappingEndpoint("App\\Entity\\OtherClass", 'http://test');
        self::assertFalse($endpoint->matches("App\\Entity\\Test"));
    }

    public function testMatchesWildcardFalseOtherNS() : void
    {
        $endpoint = new MappingEndpoint("App\\Entity\\*Class", 'http://test');
        self::assertFalse($endpoint->matches("OtherApp\\Entity\\TestClass"));
    }

    public function testMatchesOldBrokenCase() : void
    {
        $endpoint = new MappingEndpoint("App\\Entity\\ApiSend", 'http://test');
        self::assertTrue($endpoint->matches("App\\Entity\\ApiSend"));
    }

    public function testGetUrl_Standard() : void
    {
        $endpoint = new MappingEndpoint("App\\Entity\\ApiSend", 'http://test/apisend');
        self::assertEquals('http://test/apisend', $endpoint->getUrl([]));
    }

    public function testGetUrl_PathId() : void
    {
        $endpoint = new MappingEndpoint("App\\Entity\\ApiSend", 'http://test/{id}/apisend');
        self::assertEquals('http://test/testuuid/apisend', $endpoint->getUrl(['id' => 'testuuid']));
    }

    public function testGetUrl_TwoIdsPaths() : void
    {
        $endpoint = new MappingEndpoint("App\\Entity\\ApiSend", '/api/v1/exams/{examId}/questions/{questionId}/answers');
        self::assertEquals('/api/v1/exams/testexamid/questions/testquestionid/answers', $endpoint->getUrl(['examId' => 'testexamid', "questionId" => 'testquestionid']));
    }

    public function testGetUrl_PathId_Missing() : void
    {
        $this->expectException(JsonLDException::class);
        $endpoint = new MappingEndpoint("App\\Entity\\ApiSend", 'http://test/{id}/apisend');
        self::assertEquals('http://test/testuuid/apisend', $endpoint->getUrl([]));
    }

    public function testNormaliseData_NoMapping(): void
    {
        $endpoint = new MappingEndpoint("App\\Entity\\ApiSend", 'http://test/{id}/apisend');
        $data = [
            "@id" => "id123",
            "_id" => "id456",
            "attributes" => ["a", "b", "c"],
            "other" => 616
        ];

        $res = $endpoint->normaliseData($data);
        self::assertEquals($data, $res);
    }

    public function testNormaliseData_WithMapping(): void
    {
        $endpoint = new MappingEndpoint("App\\Entity\\ApiSend", 'http://test/{id}/apisend', [
            'id' => '@id',
            'other' => 'someThing',
            'attributes' => 'stuff',
        ]);
        $data = [
            "id" => "id123",
            "_id" => "id456",
            "attributes" => ["a", "b", "c"],
            "other" => 616
        ];
        $expected = [
            "@id" => "id123",
            "_id" => "id456",
            "stuff" => ["a", "b", "c"],
            "someThing" => 616
        ];

        $res = $endpoint->normaliseData($data);
        self::assertEquals($expected, $res);
    }

    public function testDenormaliseData_NoMapping(): void
    {
        $endpoint = new MappingEndpoint("App\\Entity\\ApiSend", 'http://test/{id}/apisend');
        $data = [
            "@id" => "id123",
            "_id" => "id456",
            "attributes" => ["a", "b", "c"],
            "other" => 616
        ];

        $res = $endpoint->denormaliseData($data);
        self::assertEquals($data, $res);
    }

    public function testDenormaliseData_WithMapping(): void
    {
        $endpoint = new MappingEndpoint("App\\Entity\\ApiSend", 'http://test/{id}/apisend', [
            'id' => '@id',
            '_id' => 'objectId',
            'other' => 'someThing',
            'attributes' => 'stuff',
        ]);
        $data = [
            "@id" => "id123",
            "objectId" => "id456",
            "stuff" => ["a", "b", "c"],
            "someThing" => 616,
            "t" => "s",
        ];
        $expected = [
            "id" => "id123",
            "_id" => "id456",
            "attributes" => ["a", "b", "c"],
            "other" => 616,
            "t" => "s",
        ];

        $res = $endpoint->denormaliseData($data);
        self::assertEquals($expected, $res);
    }
}
