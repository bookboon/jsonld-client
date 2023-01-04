<?php


namespace Bookboon\JsonLDClient\Tests\Client;

use Bookboon\JsonLDClient\Client\CacheMiddleware;
use Bookboon\JsonLDClient\Client\JsonLDClient;
use Bookboon\JsonLDClient\Client\JsonLDNotFoundException;
use Bookboon\JsonLDClient\Client\JsonLDResponseException;
use Bookboon\JsonLDClient\Client\JsonLDSerializationException;
use Bookboon\JsonLDClient\Mapping\MappingApi;
use Bookboon\JsonLDClient\Mapping\MappingCollection;
use Bookboon\JsonLDClient\Mapping\MappingEndpoint;
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
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

class JsonLdClientTest extends TestCase
{
    const RELEVANT_HEADER = 'X-Bookboon-Header';
    const IRRELEVANT_HEADER = 'X-Bookboon-Ignoreme';

    protected MockHandler $mockHandler;

    public function setUp(): void
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

    public function testGetById_Success(): void
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

    public function testGetById_Nested_Success(): void
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

    public function testGetById_SerializationError(): void
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

    public function testGetById_ResponseCommunicationError(): void
    {
        $this->expectException(JsonLDResponseException::class);

        $client = $this->getClient("");
        $this->mockHandler->reset();
        $this->mockHandler->append(new RequestException('Error Communicating with Server', new Request('GET', 'test')));
        $client->getById('bce73a1e-bc1f-43f5-b8dc-f05147f18978', SimpleClass::class);
    }

    public function testGetById_ResponseNotFoundError(): void
    {
        $this->expectException(JsonLDNotFoundException::class);

        $client = $this->getClient("");
        $this->mockHandler->reset();
        $this->mockHandler->append(new Response(404, [], '{"errors":[{"status": "404", "title": "Bad Request"}]}'));
        $client->getById('bce73a1e-bc1f-43f5-b8dc-f05147f18978', SimpleClass::class);
    }

    public function testGetById_ResponseBadRequestError(): void
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

    public function testGetById_ResponseBadRequestError2(): void
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

    public function testGetMany_Success(): void
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

    public function testGetMany_SuccessWithNoResponseHeader(): void
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
            },
            {
                "@type": "SimpleClass",
                "value": "test value 3"
            },
            {
                "@type": "SimpleClass",
                "value": "test value 4"
            },
            {
                "@type": "SimpleClass",
                "value": "test value 5"
            },
            {
                "@type": "SimpleClass",
                "value": "test value 6"
            },
            {
                "@type": "SimpleClass",
                "value": "test value 7"
            },
            {
                "@type": "SimpleClass",
                "value": "test value 8"
            },
            {
                "@type": "SimpleClass",
                "value": "test value 9"
            },
            {
                "@type": "SimpleClass",
                "value": "test value 10"
            },
            {
                "@type": "SimpleClass",
                "value": "test value 11"
            },
            {
                "@type": "SimpleClass",
                "value": "test value 12"
            },
            {
                "@type": "SimpleClass",
                "value": "test value 13"
            }            
        ]
        JSON;

        $client = $this->getClient($testJson);
        $entities = $client->getMany(SimpleClass::class, []);

        self::assertCount(13, $entities);
        self::assertCount(13, iterator_to_array($entities));

        foreach ($entities  as $key => $singleEntity) {
            $expected = sprintf("test value %s", strval($key + 1));
            self::assertEquals($expected, $singleEntity->getValue());
        }

        self::assertNotNull($this->mockHandler->getLastRequest());
        self::assertEquals('/simple', $this->mockHandler->getLastRequest()->getUri()->getPath());
    }

    public function testPersist_Update(): void
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

    public function testPersist_Create(): void
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

    public function testPersist_NonCollection_Create(): void
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

    public function testDelete_Success(): void
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
    public function testGetById_Cache_Hit(): void
    {
        $testJson = <<<JSON
        {
            "@type": "SimpleClass",
            "value": "test value 1"
        }
        JSON;

        /** @var Stub&CacheInterface $cacheStub */
        $cacheStub = $this->createStub(CacheInterface::class);
        $hourAgo = (new \DateTime())->modify("-1 hour");
        $cacheStub->method('get')
            ->willReturnCallback(function ($key) use ($testJson, $hourAgo) {
                if (preg_match('#^jsonld_[^_]+$#', $key)) {
                    return [
                        'headers' => [],
                        'timestamp' => $hourAgo,
                    ];
                }
                return [
                    'response' => new Response(200, [], $testJson),
                    'timestamp' => new \DateTime(),
                ];
            });

        $client = $this->getClient($testJson, $cacheStub);
        $entity = $client->getById('bce73a1e-bc1f-43f5-b8dc-f05147f18978', SimpleClass::class, []);

        self::assertInstanceOf(SimpleClass::class, $entity);
        self::assertEquals('test value 1', $entity->getValue());

        self::assertNull($this->mockHandler->getLastRequest());
    }

    /**
     * @group test
     */
    public function testGetById_Cache_Miss(): void
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

        $cacheStub->expects(self::exactly(2))
            ->method('set')
            ->withConsecutive([self::equalTo('jsonld_simple--bce73a1e-bc1f-43f5-b8dc-f05147f18978'), self::callback(function ($val) {
                if (!isset($val['timestamp'], $val['headers'])) {
                    return false;
                }

                if (!($val['timestamp'] instanceof \DateTimeImmutable)) {
                    return false;
                }

                if (!is_array($val['headers']) || !empty($val['headers'])) {
                    return false;
                }

                return true;
            })], [self::equalTo('jsonld_simple--bce73a1e-bc1f-43f5-b8dc-f05147f18978_c16bfa1d7236643659009b46f457c8d15b0d13b1'), self::callback(function ($val) use ($testJson) {
                if (!isset($val['timestamp'], $val['response'])) {
                    return false;
                }

                if (!($val['timestamp'] instanceof \DateTimeImmutable)) {
                    return false;
                }

                if (!($val['response'] instanceof ResponseInterface)) {
                    return false;
                }

                if ($val['response']->getBody()->getContents() != $testJson) {
                    return false;
                }

                return true;
            })]);

        $client = $this->getClient($testJson, $cacheStub);
        $entity = $client->getById('bce73a1e-bc1f-43f5-b8dc-f05147f18978', SimpleClass::class, []);

        self::assertInstanceOf(SimpleClass::class, $entity);
        self::assertEquals('test value 1', $entity->getValue());

        self::assertNotNull($this->mockHandler->getLastRequest());
        self::assertEquals('/simple/bce73a1e-bc1f-43f5-b8dc-f05147f18978', $this->mockHandler->getLastRequest()->getUri()->getPath());
    }

    public function testUpdate_Cache_Success(): void
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

    public function testUpdate_Cache_Not_Found(): void
    {
        $testJson = <<<JSON
        {
            "@type": "SimpleClass",
            "value": "test value 1"
        }
        JSON;

        /** @var MockObject&CacheInterface $cacheStub */
        $cacheStub = $this->createStub(CacheInterface::class);
        $cacheStub->expects(self::exactly(2))
            ->method('set');

        self::expectException(JsonLDNotFoundException::class);

        $client = $this->getClient($testJson, $cacheStub, 404);
        $client->getById('bce73a1e-bc1f-43f5-b8dc-f05147f18978', SimpleClass::class, []);
    }


    public function testUpdate_Cache_Error_Codes_No_Cache(): void
    {
        $testJson = <<<JSON
        {
            "@type": "SimpleClass",
            "value": "test value 1"
        }
        JSON;

        /** @var MockObject&CacheInterface $cacheStub */
        $cacheStub = $this->createStub(CacheInterface::class);
        $cacheStub->expects(self::never())
            ->method('set');

        foreach ([400, 401, 403, 500, 503] as $statusCode) {
            self::expectException(JsonLDResponseException::class);

            $client = $this->getClient($testJson, $cacheStub, $statusCode);
            $client->getById('bce73a1e-bc1f-43f5-b8dc-f05147f18978', SimpleClass::class, []);
        }
    }

    public function testDelete_Cache_Success(): void
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


    public function testGetById_Other_Success(): void
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
            new Response(200, [], '[
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

        $iterator = $client->getMany(SimpleClass::class, ['a' => 'b', 'b' => 'c']);
        self::assertCount(2, $iterator);

        $iterator = $client->getMany(SimpleClass::class, ['a' => 'b', 'b' => 'c']);
        self::assertCount(2, $iterator);

        $iterator = $client->getMany(SimpleClass::class, ['b' => 'c', 'a' => 'b']);
        self::assertCount(2, $iterator);

        $iterator = $client->getMany(SimpleClass::class, ['a' => 'c']);
        self::assertCount(0, $iterator);
    }

    public function testGetAll_Cached_Vary(): void
    {
        $cache = new MemoryCache;
        $headerValue = '3';
        $irrelevantValue = 'i like cookies';
        $middleware = function (callable $handler) use (&$headerValue, &$irrelevantValue) {
            return function (RequestInterface $request, array $options) use (&$handler, &$headerValue, &$irrelevantValue) {
                $request = $request->withHeader(self::RELEVANT_HEADER, $headerValue);
                $request = $request->withHeader(self::IRRELEVANT_HEADER, $irrelevantValue);
                return $handler($request, $options);
            };
        };
        $client = $this->getClientForResponses([
            new Response(200, [
                'Vary' => self::RELEVANT_HEADER . ", Accept-Language",
            ], '[
                {
                    "@type": "SimpleClass",
                    "value": "test value 1"
                },
                {
                    "@type": "SimpleClass",
                    "value": "test value 2"
                }
            ]'), new Response(200, [], '[]')
        ], $cache, [$middleware]);

        $iterator = $client->getMany(SimpleClass::class, ['a' => 'b', 'b' => 'c']);
        self::assertCount(2, $iterator);

        // re-do with same headers
        $iterator = $client->getMany(SimpleClass::class, ['a' => 'b', 'b' => 'c']);
        self::assertCount(2, $iterator);

        // re-do with only irrelevant header changed
        $irrelevantValue = 'i do not like cookies';
        $iterator = $client->getMany(SimpleClass::class, ['a' => 'b', 'b' => 'c']);
        self::assertCount(2, $iterator);

        // re-do with different header
        $headerValue = '4';
        $iterator = $client->getMany(SimpleClass::class, ['a' => 'b', 'b' => 'c']);
        self::assertCount(0, $iterator);
    }

    public function testGetAll_Cached_Invalidate(): void
    {
        $cache = new MemoryCache;
        $headerValue = '3';
        $irrelevantValue = 'i like cookies';
        $middleware = function (callable $handler) use (&$headerValue, &$irrelevantValue) {
            return function (RequestInterface $request, array $options) use (&$handler, &$headerValue, &$irrelevantValue) {
                $request = $request->withHeader(self::RELEVANT_HEADER, $headerValue);
                $request = $request->withHeader(self::IRRELEVANT_HEADER, $irrelevantValue);
                return $handler($request, $options);
            };
        };
        $client = $this->getClientForResponses([
            new Response(200, [
                'Vary' => self::RELEVANT_HEADER . ", Accept-Language",
            ], '[
                {
                    "@type": "SimpleClass",
                    "value": "test value 1"
                },
                {
                    "@type": "SimpleClass",
                    "value": "test value 2"
                }
            ]'), new Response(200, [], '{
                    "@type": "SimpleClass",
                    "value": "test value 3"
            }'), new Response(200, [], '[
                {
                    "@type": "SimpleClass",
                    "value": "test value 1"
                },
                {
                    "@type": "SimpleClass",
                    "value": "test value 2"
                },
                {
                    "@type": "SimpleClass",
                    "value": "test value 3"
                }
            ]')
        ], $cache, [$middleware]);

        $iterator = $client->getMany(SimpleClass::class, ['a' => 'b', 'b' => 'c']);
        self::assertCount(2, $iterator);

        // post to endpoint
        $new = new SimpleClass();
        $new->setValue('test value 3');
        $client->create($new);

        // re-do with same headers
        $iterator = $client->getMany(SimpleClass::class, ['a' => 'b', 'b' => 'c']);
        self::assertCount(3, $iterator);
    }

    public function testGetAll_UnCached(): void
    {
        $cache = new MemoryCache;
        $client = $this->getClientForResponses([
            new Response(200, [], '[
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

        $iterator = $client->getMany(SimpleClass::class, ['a' => 'b'], false);
        self::assertCount(2, $iterator);

        $iterator = $client->getMany(SimpleClass::class, ['a' => 'b'], false);
        self::assertCount(0, $iterator);
    }


    public function testGetById_Other_Nested_Success(): void
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

    protected function getClient(string $body, CacheInterface $cache = null, $statusCode = 200): JsonLDClient
    {
        return $this->getClientForResponses([
            new Response($statusCode, [], $body)
        ], $cache);
    }

    protected function getClientForResponses(array $responses, ?CacheInterface $cache = null, array $extraMiddleware = []): JsonLDClient
    {
        $this->mockHandler = new MockHandler($responses);

        $handlerStack = HandlerStack::create($this->mockHandler);

        foreach ($extraMiddleware as $m) {
            $handlerStack->push($m);
        }

        if ($cache) {
            $handlerStack->push(new CacheMiddleware($cache));
        }

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
        );
    }
}
