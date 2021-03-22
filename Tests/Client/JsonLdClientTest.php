<?php


namespace Bookboon\JsonLDClient\Tests\Client;

use Bookboon\JsonLDClient\Client\JsonLDClient;
use Bookboon\JsonLDClient\Client\JsonLDNotFoundException;
use Bookboon\JsonLDClient\Client\JsonLDResponseException;
use Bookboon\JsonLDClient\Client\JsonLDSerializationException;
use Bookboon\JsonLDClient\Mapping\MappingCollection;
use Bookboon\JsonLDClient\Mapping\MappingEndpoint;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\SimpleClass;
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

    public function testPersist_Success() : void
    {
        $testJson = <<<JSON
        {
            "@type": "SimpleClass",
            "value": "test value 2"
        }
        JSON;

        $testObject = new SimpleClass();
        $testObject->setValue("test value 2");

        $client = $this->getClient($testJson);
        $entity = $client->persist($testObject);

        self::assertInstanceOf(SimpleClass::class, $entity);
        self::assertEquals('test value 2', $entity->getValue());

        self::assertNotNull($this->mockHandler->getLastRequest());
        self::assertEquals('/simple/' . $testObject->getId(), $this->mockHandler->getLastRequest()->getUri()->getPath());
    }

    public function testPersist_Update() : void
    {
        $testJson = <<<JSON
        {
            "@type": "SimpleClass",
            "value": "test value 3"
        }
        JSON;

        $testObject = new SimpleClass();
        $testObject->setValue("test value 2");

        $client = $this->getClient($testJson);
        $entity = $client->update($testObject);

        self::assertInstanceOf(SimpleClass::class, $entity);
        self::assertEquals('test value 3', $entity->getValue());

        self::assertNotNull($this->mockHandler->getLastRequest());
        self::assertEquals("PUT", $this->mockHandler->getLastRequest()->getMethod());
        self::assertEquals('/simple/' . $testObject->getId(), $this->mockHandler->getLastRequest()->getUri()->getPath());
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

    public function testPersist_Cache_Success() : void
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
        $entity = $client->persist($testObject);

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

    protected function getClient(string $body, CacheInterface $cache = null) : JsonLDClient
    {
        $this->mockHandler = new MockHandler(
            [
                new Response(200, [], $body)
            ]
        );

        $handlerStack = HandlerStack::create($this->mockHandler);
        $client = new Client(
            [
                'handler' => $handlerStack,
                'headers' => [
                    'User-Agent' => 'TestClient/0.3'
                ]
            ]
        );

        return new JsonLDClient(
            $client,
            SerializerHelper::create([]),
            new MappingCollection(
                [
                new MappingEndpoint(SimpleClass::class, 'http://localhost/simple')
                ]
            ),
            $cache
        );
    }
}
