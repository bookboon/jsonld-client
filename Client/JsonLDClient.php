<?php

namespace Bookboon\JsonLDClient\Client;

use ArrayAccess;
use Bookboon\JsonLDClient\Mapping\MappingCollection;
use Bookboon\JsonLDClient\Mapping\MappingEndpoint;
use Bookboon\JsonLDClient\Models\ApiErrorResponse;
use Bookboon\JsonLDClient\Models\ApiIterable;
use Bookboon\JsonLDClient\Serializer\JsonLDEncoder;
use Bookboon\JsonLDClient\Serializer\JsonLDNormalizer;
use Countable;
use Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Iterator;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\SerializerInterface;

class JsonLDClient
{
    private SerializerInterface $_serializer;
    private MappingCollection $_mappings;
    private ClientInterface $_client;

    private ?AccessTokenInterface $accessToken = null;

    /**
     * JsonLdClient constructor.
     *
     */
    public function __construct(
        ClientInterface $client,
        SerializerInterface $serializer,
        MappingCollection $mappings,
    )
    {
        $this->_client = $client;
        $this->_serializer = $serializer;
        $this->_mappings = $mappings;
    }

    /**
     * @template T
     * @param T $object
     * @param array $params
     * @return T
     * @throws JsonLDNotFoundException
     * @throws JsonLDResponseException
     * @throws JsonLDSerializationException
     */
    public function create($object, array $params = [])
    {
        $map = $this->_mappings->findEndpointByClass(get_class($object));
        $this->isValidObjectOrException($object, false, $map->isSingleton());

        return $this->prepareRequest($object, 'POST', $this->getUrl($object, $params), $map, $params);
    }

    /**
     * @template T
     * @param T $object
     * @param array $params
     * @return T
     * @throws JsonLDNotFoundException
     * @throws JsonLDResponseException
     * @throws JsonLDSerializationException
     */
    public function update($object, array $params = [])
    {
        $map = $this->_mappings->findEndpointByClass(get_class($object));
        $this->isValidObjectOrException($object, true, $map->isSingleton());

        $url = $this->getUrl($object, $params);
        if (false === $map->isSingleton()) {
            $url = sprintf('%s/%s', $url, $object->getId());
        }

        return $this->prepareRequest($object, "PUT", $url, $map, $params);
    }

    public function delete(object $object, array $params = []): void
    {
        $this->isValidObjectOrException($object, true);

        $map = $this->_mappings->findEndpointByClass(get_class($object));

        $url = $this->getUrl($object, $params);
        if (false === $map->isSingleton()) {
            $url = sprintf('%s/%s', $url, $object->getId());
        }

        // we must use the cache here so that it is correctly invalidated
        $this->makeRequest($url, 'DELETE', [], '', true);
    }

    /**
     * @template T
     * @psalm-param class-string<T> $className
     * @param array $params
     * @param bool $useCache
     * @return Iterator<T>&ArrayAccess&Countable
     * @throws JsonLDException
     */
    public function getMany(string $className, array $params, bool $useCache = true): Iterator
    {
        $map = $this->_mappings->findEndpointByClass($className);

        if ($map->isSingleton()) {
            throw new JsonLDException('Cannot getMany on non-collection');
        }

        $url = $map->getUrl($params);

        /** @var ApiIterable<T> $iter */
        $iter = new ApiIterable(
            function (array $params2) use ($url, $useCache) {
                ksort($params2);
                $response = $this->makeRequest($url, 'GET', $params2, null, $useCache);

                $serialised = [
                    'code' => $response->getStatusCode(),
                    'headers' => $response->getHeaders(),
                    'version' => $response->getProtocolVersion(),
                    'reason' => $response->getReasonPhrase(),
                    'body' => $response->getBody()->getContents(),
                ];

                return new Response($serialised['code'],
                    $serialised['headers'],
                    $serialised['body'],
                    $serialised['version'],
                    $serialised['reason']
                );
            },
            fn(string $data) => $this->deserialize($data, $map),
            $params
        );

        return $iter;
    }

    /**
     * @template T
     * @psalm-param class-string<T> $className
     * @param array $params
     * @param bool $useCache
     * @return T
     * @throws JsonLDException
     * @throws JsonLDNotFoundException
     * @throws JsonLDResponseException
     * @throws JsonLDSerializationException
     */
    public function getSingleton(string $className, array $params = [], bool $useCache = false)
    {
        return $this->getById('', $className, $params, $useCache);
    }

    /**
     * @template T
     * @param string $id
     * @psalm-param class-string<T> $className
     * @param array $params
     * @param bool $useCache
     * @return T
     * @throws JsonLDException
     * @throws JsonLDNotFoundException
     * @throws JsonLDResponseException
     * @throws JsonLDSerializationException
     */
    public function getById(string $id, string $className, array $params = [], bool $useCache = true)
    {
        $map = $this->_mappings->findEndpointByClass($className);

        $url = $map->getUrl($params);
        if (false === $map->isSingleton()) {
            $url = sprintf('%s/%s', $url, $id);
        }

        $response = $this->makeRequest($url, 'GET', $params, null, $useCache);
        $jsonContents = $response->getBody()->getContents();

        /** @var T $object */
        $object = $this->deserialize($jsonContents, $map);

        return $object;
    }

    /**
     * @param AccessTokenInterface|null $token
     * @return void
     */
    public function setAccessToken(?AccessTokenInterface $token): void
    {
        $this->accessToken = $token;
    }

    /**
     * @return AccessTokenInterface|null
     */
    public function getAccessToken(): ?AccessTokenInterface
    {
        return $this->accessToken;
    }

    /**
     * @template T
     * @param T $object
     * @param string $httpVerb
     * @param string $url
     * @param MappingEndpoint $map
     * @param array $params
     * @return T
     * @throws JsonLDNotFoundException
     * @throws JsonLDResponseException
     * @throws JsonLDSerializationException
     */
    protected function prepareRequest(
        $object,
        string $httpVerb,
        string $url,
        MappingEndpoint $map,
        array $params = []
    )
    {
        $jsonContents = $this->_serializer->serialize($object, JsonLDEncoder::FORMAT);

        // we must use the cache here so that it is correctly invalidated
        $response = $this->makeRequest($url, $httpVerb, $params, $jsonContents, true);

        /** @var T $object */
        $object = $this->deserialize(
            $response->getBody()->getContents(),
            $map
        );

        return $object;
    }

    protected function getUrl(object $object, array $params = []): string
    {
        $map = $this->_mappings->findEndpointByClass(get_class($object));

        return $map->getUrl($params);
    }

    /**
     * @param string $url
     * @param string $httpVerb
     * @param array $queryParams
     * @param string|null $jsonContents
     * @param bool $useCache
     * @return ResponseInterface
     * @throws JsonLDResponseException
     * @throws JsonLDNotFoundException
     */
    protected function makeRequest(
        string  $url,
        string  $httpVerb = 'GET',
        array   $queryParams = [],
        ?string $jsonContents = null,
        bool $useCache = false,
    ): ResponseInterface
    {

        $headers = [
            'Accept-Encoding' => 'application/json',
            'Content-Type' => 'application/json'
        ];

        if (null !== $token = $this->accessToken) {
            $headers['Authorization'] = "Bearer {$token->getToken()}";
        }

        $requestParams = [
            RequestOptions::HEADERS => $headers,
            RequestOptions::QUERY => $queryParams
        ];

        if ($jsonContents !== null) {
            $requestParams[RequestOptions::BODY] = $jsonContents;
        }

        $requestParams[CacheMiddleware::USE_CACHE] = $useCache;

        // these two are here because psalm complained without them
        $response = null;
        $e = null;
        try {
            return $this->_client->request(
                $httpVerb,
                $url,
                $requestParams
            );
        } catch (RequestException $e) {
            if ($e->hasResponse() && null !== ($response = $e->getResponse())) {
                if ($response->getStatusCode() === 404) {
                    throw new JsonLDNotFoundException();
                }

                $errorResponse = null;

                try {
                    /** @var ApiErrorResponse $errorResponse */
                    $errorResponse = $this->deserialize(
                        $response->getBody()->getContents(),
                        null,
                        ApiErrorResponse::class
                    );
                } catch (JsonLDSerializationException $e2) {
                }

                throw new JsonLDResponseException(
                    $e->getMessage(),
                    $response->getStatusCode(),
                    $e,
                    $errorResponse
                );
            }

            throw new JsonLDResponseException("Unknown error", 0, $e);
        }
    }

    /**
     * @param object $object
     * @param bool $checkId
     * @return void
     * @throws JsonLDException
     */
    protected function isValidObjectOrException(object $object, bool $checkId = false, bool $singleton = false): void
    {
        if ($singleton) {
            return;
        }

        if (false === method_exists($object, 'getId')) {
            throw new JsonLDException('Cannot persist object without getId method');
        }

        if ($checkId && strlen($object->getId()) === 0) {
            throw new JsonLDException('Invalid object id');
        }
    }

    /**
     * @param string $jsonContents
     * @param MappingEndpoint|null $map
     * @param string $type
     * @param string $format
     * @return array<object>|object
     * @throws JsonLDSerializationException
     */
    protected function deserialize(
        string           $jsonContents,
        ?MappingEndpoint $map,
        string           $type = '',
        string           $format = JsonLDEncoder::FORMAT
    )
    {
        $context = [];
        if ($map !== null) {
            $context[JsonLDNormalizer::MAPPPING_KEY] = $map;
        }

        try {
            return $this->_serializer->deserialize($jsonContents, $type, $format, $context);
        } catch (Exception $e) {
            throw new JsonLDSerializationException($e->getMessage(), 0, $e);
        }
    }
}
