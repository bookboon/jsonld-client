<?php


namespace Bookboon\JsonLDClient\Tests\Client;

use Bookboon\JsonLDClient\Client\JsonLDClient;
use Bookboon\JsonLDClient\Client\JsonLDNotFoundException;
use Bookboon\JsonLDClient\Client\JsonLDResponseException;
use Bookboon\JsonLDClient\Client\JsonLDSerializationException;
use Bookboon\JsonLDClient\Mapping\MappingApi;
use Bookboon\JsonLDClient\Mapping\MappingCollection;
use Bookboon\JsonLDClient\Mapping\MappingEndpoint;
use Bookboon\JsonLDClient\Models\ApiIterable;
use Bookboon\JsonLDClient\Tests\Fixtures\MemoryCache;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\NestedClass;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\SimpleClass;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\NestedArrayClass;
use Bookboon\JsonLDClient\Tests\Fixtures\OtherModels\SimpleClass as OtherSimpleClass;
use Bookboon\JsonLDClient\Tests\Fixtures\OtherModels\NestedArrayClass as OtherNestedArrayClass;
use Bookboon\JsonLDClient\Tests\Fixtures\SerializerHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class JsonLdClientTest extends TestCase
{
    protected MockHandler $mockHandler;

    public function setUp() : void
    {
        $this->mockHandler = new MockHandler(
            [
                new Response(200, [], '')
            ]
        );
    }

    /*
     * Plain Client
     */

    public function testGetById_Success() : void
    {
        $testJson = <<<JSON
        {
            "@type": "SimpleClass",
            "value": "test value 1"
        }
        JSON;

        $client = $this->getClient($testJson);
        $entity = $client->getById('bce73a1e-bc1f-43f5-b8dc-f05147f18978', SimpleClass::class);

        self::assertInstanceOf(SimpleClass::class, $entity);
        self::assertEquals('test value 1', $entity->getValue());

        self::assertNotNull($this->mockHandler->getLastRequest());
        self::assertEquals('/simple/bce73a1e-bc1f-43f5-b8dc-f05147f18978', $this->mockHandler->getLastRequest()->getUri()->getPath());
    }

    public function testGetById_Nested_Success() : void
    {
        $testJson = <<<JSON
        {
            "@type": "NestedArrayClass",
            "string": "some random string",
            "simpleClasses": [
                {
                    "@type": "SimpleClass",
                    "value": "some other string"
                },
                {
                    "@type": "SimpleClass",
                    "value": "different value"
                }
            ]
        }
        JSON;

        $client = $this->getClient($testJson);
        $entity = $client->getById('bce73a1e-bc1f-43f5-b8dc-f05147f18978', NestedArrayClass::class);

        self::assertInstanceOf(NestedArrayClass::class, $entity);
        self::assertEquals('some random string', $entity->getString());
        self::assertCount(2, $entity->getSimpleClasses());
        self::assertEquals('some other string', $entity->getSimpleClasses()[0]->getValue());
        self::assertEquals('different value', $entity->getSimpleClasses()[1]->getValue());

        self::assertNotNull($this->mockHandler->getLastRequest());
        self::assertEquals('/nestedarray/bce73a1e-bc1f-43f5-b8dc-f05147f18978', $this->mockHandler->getLastRequest()->getUri()->getPath());
    }

    public function testGetById_SerializationError() : void
    {
        $this->expectException(JsonLDSerializationException::class);
        $testJson = <<<JSON
        {
            "@type": "SimpleClass",
            "value": 
        }
        JSON;

        $client = $this->getClient($testJson);
        $client->getById('bce73a1e-bc1f-43f5-b8dc-f05147f18978', SimpleClass::class);
    }

    public function testGetById_ResponseCommunicationError() : void
    {
        $this->expectException(JsonLDResponseException::class);

        $client = $this->getClient("");
        $this->mockHandler->reset();
        $this->mockHandler->append(new RequestException('Error Communicating with Server', new Request('GET', 'test')));
        $client->getById('bce73a1e-bc1f-43f5-b8dc-f05147f18978', SimpleClass::class);
    }

    public function testGetById_ResponseNotFoundError() : void
    {
        $this->expectException(JsonLDNotFoundException::class);

        $client = $this->getClient("");
        $this->mockHandler->reset();
        $this->mockHandler->append(new Response(404, [], '{"errors":[{"status": "404", "title": "Bad Request"}]}'));
        $client->getById('bce73a1e-bc1f-43f5-b8dc-f05147f18978', SimpleClass::class);
    }

    public function testGetById_ResponseBadRequestError() : void
    {
        $this->expectException(JsonLDResponseException::class);
        $this->expectExceptionMessage("400: Bad Request");

        $client = $this->getClient("");
        $this->mockHandler->reset();
        $this->mockHandler->append(
            new RequestException(
                'Error Communicating with Server',
                new Request('GET', 'test'),
                new Response(400, [], '{"errors":[{"status": "400", "title": "Bad Request"}]}')
            )
        );
        $client->getById('bce73a1e-bc1f-43f5-b8dc-f05147f18978', SimpleClass::class);
    }

    public function testGetById_ResponseBadRequestError2() : void
    {
        $this->expectException(JsonLDResponseException::class);
        $this->expectExceptionMessage("400: Bad request");

        $client = $this->getClient("");
        $this->mockHandler->reset();
        $this->mockHandler->append(
            new RequestException(
                'Error Communicating with Server',
                new Request('GET', 'test'),
                new Response(400, [], '{"errors":[{"status":"400","title":"Bad request","detail":"Validation failed for field with tag: gte","source":{"pointer":"/shortDescription"}}]}')
            )
        );
        $client->getById('bce73a1e-bc1f-43f5-b8dc-f05147f18978', SimpleClass::class);
    }

    public function testGetMany_Success() : void
    {
        $testJson = <<<JSON
        [
            {
                "@type": "SimpleClass",
                "value": "test value 1"
            },
            {
                "@type": "SimpleClass",
                "value": "test value 2"
            }
        ]
        JSON;

        $client = $this->getClient($testJson);
        $entities = $client->getMany(SimpleClass::class, []);

        self::assertCount(2, $entities);

        self::assertNotNull($this->mockHandler->getLastRequest());
        self::assertEquals('/simple', $this->mockHandler->getLastRequest()->getUri()->getPath());
    }

    public function testPersist_Update() : void
    {
        $testJson = <<<JSON
        {
            "@type": "NestedClass",
            "string": "test value 3",
            "simpleClass": {
                "@type": "SimpleClass",
                "value": "test value 1"
            }
        }
        JSON;

        $testObject = new NestedClass();
        $testObject->setString("test value 2");
        $simple = new SimpleClass();
        $simple->setValue('test value 1');
        $testObject->setSimpleClass($simple);

        $client = $this->getClient($testJson);
        $entity = $client->update($testObject);

        self::assertInstanceOf(NestedClass::class, $entity);
        self::assertEquals('test value 3', $entity->getString());

        $simple = $entity->getSimpleClass();
        self::assertNotNull($simple);
        self::assertInstanceOf(SimpleClass::class, $simple);
        self::assertEquals('test value 1', $simple->getValue());

        self::assertNotNull($this->mockHandler->getLastRequest());
        self::assertEquals("PUT", $this->mockHandler->getLastRequest()->getMethod());
        self::assertEquals('/nested', $this->mockHandler->getLastRequest()->getUri()->getPath());
    }

    public function testPersist_Create() : void
    {
        $testJson = <<<JSON
        {
            "@type": "SimpleClass",
            "value": "test value 3"
        }
        JSON;

        $testObject = new SimpleClass();
        $testObject->setValue("test value 3");

        $client = $this->getClient($testJson);
        $entity = $client->create($testObject);

        self::assertInstanceOf(SimpleClass::class, $entity);
        self::assertEquals('test value 3', $entity->getValue());

        self::assertNotNull($this->mockHandler->getLastRequest());
        self::assertEquals("POST", $this->mockHandler->getLastRequest()->getMethod());
        self::assertEquals('/simple', $this->mockHandler->getLastRequest()->getUri()->getPath());
    }

    public function testPersist_NonCollection_Create() : void
    {
        $testJson = <<<JSON
        {
            "@type": "SimpleClass",
            "value": "test value 3"
        }
        JSON;

        $testObject = new SimpleClass();
        $testObject->setValue("test value 3");

        $client = $this->getClient($testJson);
        $entity = $client->create($testObject);

        self::assertInstanceOf(SimpleClass::class, $entity);
        self::assertEquals('test value 3', $entity->getValue());

        self::assertNotNull($this->mockHandler->getLastRequest());
        self::assertEquals("POST", $this->mockHandler->getLastRequest()->getMethod());
        self::assertEquals('/simple', $this->mockHandler->getLastRequest()->getUri()->getPath());
    }

    public function testDelete_Success() : void
    {
        $testObject = new SimpleClass();
        $testObject->setValue("test value 2");

        $client = $this->getClient("{}");
        $client->delete($testObject, []);

        self::assertNotNull($this->mockHandler->getLastRequest());
        self::assertEquals('DELETE', $this->mockHandler->getLastRequest()->getMethod());
        self::assertEquals('/simple/' . $testObject->getId(), $this->mockHandler->getLastRequest()->getUri()->getPath());
    }

    /*
     * Cached Client
     */

    /**
     * @group test
     */
    public function testGetById_Cache_Hit() : void
    {
        $testJson = <<<JSON
        {
            "@type": "SimpleClass",
            "value": "test value 1"
        }
        JSON;

        /** @var Stub&CacheInterface $cacheStub */
        $cacheStub = $this->createStub(CacheInterface::class);
        $cacheStub->method('get')
            ->willReturn($testJson);

        $client = $this->getClient($testJson, $cacheStub);
        $entity = $client->getById('bce73a1e-bc1f-43f5-b8dc-f05147f18978', SimpleClass::class, [], true);

        self::assertInstanceOf(SimpleClass::class, $entity);
        self::assertEquals('test value 1', $entity->getValue());

        self::assertNull($this->mockHandler->getLastRequest());
    }

    /**
     * @group test
     */
    public function testGetById_Cache_Miss() : void
    {
        $testJson = <<<JSON
        {
            "@type": "SimpleClass",
            "value": "test value 1"
        }
        JSON;

        /** @var MockObject&CacheInterface $cacheStub */
        $cacheStub = $this->createStub(CacheInterface::class);
        $cacheStub->method('get')
            ->willReturn(null);

        $cacheStub->expects(self::once())
            ->method('set')
            ->with(self::equalTo('jsonld_bce73a1e-bc1f-43f5-b8dc-f05147f18978'), self::equalTo($testJson));

        $client = $this->getClient($testJson, $cacheStub);
        $entity = $client->getById('bce73a1e-bc1f-43f5-b8dc-f05147f18978', SimpleClass::class, [], true);

        self::assertInstanceOf(SimpleClass::class, $entity);
        self::assertEquals('test value 1', $entity->getValue());

        self::assertNotNull($this->mockHandler->getLastRequest());
        self::assertEquals('/simple/bce73a1e-bc1f-43f5-b8dc-f05147f18978', $this->mockHandler->getLastRequest()->getUri()->getPath());
    }

    public function testUpdate_Cache_Success() : void
    {
        $testJson = <<<JSON
        {
            "@type": "SimpleClass",
            "value": "test value 2"
        }
        JSON;

        /** @var MockObject&CacheInterface $cacheStub */
        $cacheStub = $this->createStub(CacheInterface::class);
        $cacheStub->expects(self::once())
            ->method('delete');

        $testObject = new SimpleClass();
        $testObject->setValue("test value 2");

        $client = $this->getClient($testJson, $cacheStub);
        $entity = $client->update($testObject);

        self::assertInstanceOf(SimpleClass::class, $entity);
        self::assertEquals('test value 2', $entity->getValue());

        self::assertNotNull($this->mockHandler->getLastRequest());
        self::assertEquals('/simple/' . $testObject->getId(), $this->mockHandler->getLastRequest()->getUri()->getPath());
    }

    public function testDelete_Cache_Success() : void
    {
        $testObject = new SimpleClass();
        $testObject->setValue("test value 2");

        /** @var MockObject&CacheInterface $cacheStub */
        $cacheStub = $this->createStub(CacheInterface::class);
        $cacheStub->expects(self::once())
            ->method('delete');

        $client = $this->getClient("{}", $cacheStub);
        $client->delete($testObject, []);

        self::assertNotNull($this->mockHandler->getLastRequest());
        self::assertEquals('DELETE', $this->mockHandler->getLastRequest()->getMethod());
        self::assertEquals('/simple/' . $testObject->getId(), $this->mockHandler->getLastRequest()->getUri()->getPath());
    }


    public function testGetById_Other_Success() : void
    {
        $testJson = <<<JSON
        {
            "@type": "SimpleClass",
            "value": "test value 1"
        }
        JSON;

        $client = $this->getClient($testJson);
        $entity = $client->getById('bce73a1e-bc1f-43f5-b8dc-f05147f18978', OtherSimpleClass::class);

        self::assertInstanceOf(OtherSimpleClass::class, $entity);
        self::assertEquals('test value 1', $entity->getValue());

        self::assertNotNull($this->mockHandler->getLastRequest());
        self::assertEquals('/simple/bce73a1e-bc1f-43f5-b8dc-f05147f18978', $this->mockHandler->getLastRequest()->getUri()->getPath());
    }

    public function testGetAll_Cached(): void
    {
        $cache = new MemoryCache;
        $client = $this->getClientForResponses([
            new Response(200, [],'[
                {
                    "@type": "SimpleClass",
                    "value": "test value 1"
                },
                {
                    "@type": "SimpleClass",
                    "value": "test value 2"
                }
            ]'), new Response(200, [], '[]')
        ], $cache);

        $iterator = $client->getMany(SimpleClass::class, ['a' => 'b', 'b' => 'c'], true);
        self::assertCount(2, $iterator);

        $iterator = $client->getMany(SimpleClass::class, ['a' => 'b', 'b' => 'c'], true);
        self::assertCount(2, $iterator);

        $iterator = $client->getMany(SimpleClass::class, ['b' => 'c', 'a' => 'b'], true);
        self::assertCount(2, $iterator);

        $iterator = $client->getMany(SimpleClass::class, ['a' => 'c'], true);
        self::assertCount(0, $iterator);
    }

    public function testGetAll_UnCached(): void
    {
        $cache = new MemoryCache;
        $client = $this->getClientForResponses([
            new Response(200, [],'[
                {
                    "@type": "SimpleClass",
                    "value": "test value 1"
                },
                {
                    "@type": "SimpleClass",
                    "value": "test value 2"
                }
            ]'), new Response(200, [], '[]')
        ], $cache);

        $iterator = $client->getMany(SimpleClass::class, ['a' => 'b']);
        self::assertCount(2, $iterator);

        $iterator = $client->getMany(SimpleClass::class, ['a' => 'b']);
        self::assertCount(0, $iterator);
    }


    public function testGetById_Other_Nested_Success() : void
    {
        $testJson = <<<JSON
        {
            "@type": "NestedArrayClass",
            "string": "some random string",
            "simpleClasses": [
                {
                    "@type": "SimpleClass",
                    "value": "some other string"
                },
                {
                    "@type": "SimpleClass",
                    "value": "different value"
                }
            ]
        }
        JSON;

        $client = $this->getClient($testJson);
        $entity = $client->getById('bce73a1e-bc1f-43f5-b8dc-f05147f18978', OtherNestedArrayClass::class);

        self::assertInstanceOf(OtherNestedArrayClass::class, $entity);
        self::assertEquals('some random string', $entity->getString());
        self::assertCount(2, $entity->getSimpleClasses());
        self::assertEquals('some other string', $entity->getSimpleClasses()[0]->getValue());
        self::assertEquals('different value', $entity->getSimpleClasses()[1]->getValue());

        self::assertNotNull($this->mockHandler->getLastRequest());
        self::assertEquals('/nestedarray/bce73a1e-bc1f-43f5-b8dc-f05147f18978', $this->mockHandler->getLastRequest()->getUri()->getPath());
    }

    protected function getClient(string $body, CacheInterface $cache = null) : JsonLDClient {
        return $this->getClientForResponses([
            new Response(200, [], $body)
        ], $cache);
    }

    protected function getClientForResponses(array $responses, CacheInterface $cache = null) : JsonLDClient
    {
        $this->mockHandler = new MockHandler($responses);

        $handlerStack = HandlerStack::create($this->mockHandler);
        $client = new Client(
            [
                'handler' => $handlerStack,
                'headers' => [
                    'User-Agent' => 'TestClient/0.3'
                ]
            ]
        );

        $mappings = [
            new MappingEndpoint(SimpleClass::class, 'http://localhost/simple'),
            new MappingEndpoint(NestedArrayClass::class, 'http://localhost/nestedarray'),
            new MappingEndpoint(OtherSimpleClass::class, 'http://otherhost/simple'),
            new MappingEndpoint(OtherNestedArrayClass::class, 'http://otherhost/nestedarray'),
            new MappingEndpoint(NestedClass::class, 'http://otherhost/nested', [], true)
        ];
        $apis = [
            new MappingApi('Bookboon\JsonLDClient\Tests\Fixtures\Models', 'https://example.com/api/v1')
        ];

        return new JsonLDClient(
            $client,
            SerializerHelper::create($mappings, [], $apis),
            new MappingCollection(
                $mappings,
                $apis,
            ),
            $cache
        );
    }
}
