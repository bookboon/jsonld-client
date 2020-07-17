<?php

namespace Bookboon\JsonLDClient\Client;

use Bookboon\JsonLDClient\Mapping\MappingCollection;
use Bookboon\JsonLDClient\Mapping\MappingEndpoint;
use Bookboon\JsonLDClient\Models\ApiErrorResponse;
use Bookboon\JsonLDClient\Serializer\JsonLDEncoder;
use Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Serializer\SerializerInterface;

class JsonLDClient
{
    private const CACHE_TIME = 1800;

    private $_serializer;
    private $_mappings;
    private $_cache;
    private $_client;

    private $accessToken;

    /**
     * JsonLdClient constructor.
     * @param ClientInterface $client
     * @param SerializerInterface $serializer
     * @param MappingCollection $mappings
     * @param CacheInterface|null $cache
     */
    public function __construct(
        ClientInterface $client,
        SerializerInterface $serializer,
        MappingCollection $mappings,
        ?CacheInterface $cache
    ) {
        $this->_client = $client;
        $this->_serializer = $serializer;
        $this->_mappings = $mappings;
        $this->_cache = $cache;
    }

    public function persist(object $object, array $params = []) : object
    {
        $this->isValidObjectOrException($object);

        $map = $this->lookupMapping(get_class($object));

        $httpVerb = 'POST';
        $url = $map->getUrl($params);
        if ($object->getId()) {
            $httpVerb = 'PUT';
            $url .= '/' . $object->getId();
        }

        $jsonContents = $this->_serializer->serialize($object, JsonLDEncoder::FORMAT);
        $response = $this->makeRequest($url, $httpVerb, [], $jsonContents);

        if ($this->_cache) {
            $this->_cache->delete($this->cacheKey($object->getId()));
        }

        return $this->deserialize(
            $response->getBody()->getContents()
        );
    }

    public function delete(object $object, array $params = []) : void
    {
        $this->isValidObjectOrException($object);

        $map = $this->lookupMapping(get_class($object));

        $url = sprintf('%s/%s', $map->getUrl($params), $object->getId());

        $this->makeRequest($url, 'DELETE', [], '');

        if ($this->_cache) {
            $this->_cache->delete($this->cacheKey($object->getId()));
        }
    }

    public function getMany(string $className, array $params) : array
    {
        $map = $this->lookupMapping($className);

        $response = $this->makeRequest($map->getUrl($params),'GET', $params);
        $jsonContents = $response->getBody()->getContents();

        if ($jsonContents === '[]' || $jsonContents === "[]\n") {
            return [];
        }

        return $this->deserialize($jsonContents);
    }

    public function getById(string $id, string $className, array $params = [], bool $useCache = false) : object
    {
        $map = $this->lookupMapping($className);

        $url = sprintf('%s/%s', $map->getUrl($params), $id);
        $jsonContents = null;
        $cacheKey = $this->cacheKey($id);

        if ($useCache && $this->_cache) {
            $jsonContents = $this->_cache->get($cacheKey, null);
        }

        if ($jsonContents === null) {
            $response = $this->makeRequest($url);
            $jsonContents = $response->getBody()->getContents();
        }

        if ($useCache && $this->_cache && $jsonContents) {
            $this->_cache->set($cacheKey, $jsonContents, self::CACHE_TIME);
        }

        return $this->deserialize($jsonContents);
    }

    /**
     * @param AccessTokenInterface|null $token
     * @return void
     */
    public function setAccessToken(?AccessTokenInterface $token) : void
    {
        $this->accessToken = $token;
    }

    /**
     * @return AccessTokenInterface|null
     */
    protected function getAccessToken(): ?AccessTokenInterface
    {
        return $this->accessToken;
    }

    /**
     * @param string $className
     * @return MappingEndpoint
     * @throws JsonLDException
     */
    protected function lookupMapping(string $className) : MappingEndpoint
    {
        foreach ($this->_mappings->get() as $map) {
            if ($map->matches($className)) {
                return $map;
            }
        }

        throw new JsonLDException('Not mapping for class: ' . $className);
    }

    /**
     * @param string $url
     * @param string $httpVerb
     * @param array $queryParams
     * @param string|null $jsonContents
     * @return ResponseInterface
     * @throws JsonLDResponseException
     * @throws JsonLDNotFoundException
     */
    protected function makeRequest(
        string $url,
        string $httpVerb = 'GET',
        array $queryParams = [],
        ?string $jsonContents = null
    ) : ResponseInterface {

        $headers = [
            'Accept-Encoding' => 'application/json',
            'Content-Type' => 'application/json'
        ];

        if ($this->accessToken !== null) {
            $headers['Authorization'] = "Bearer {$this->getAccessToken()->getToken()}";
        }

        $requestParams = [
            RequestOptions::HEADERS => $headers,
            RequestOptions::QUERY => $queryParams
        ];

        if ($jsonContents !== null) {
            $requestParams[RequestOptions::BODY] = $jsonContents;
        }

        try {
            return $this->_client->request(
                $httpVerb,
                $url,
                $requestParams
            );
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                if ($e->getResponse()->getStatusCode() === 404) {
                    throw new JsonLDNotFoundException();
                }

                $errorResponse = null;

                try {
                    $errorResponse = $this->deserialize(
                        $e->getResponse()->getBody()->getContents(),
                        ApiErrorResponse::class,
                        'json'
                    );
                } catch (JsonLDSerializationException $e2) {}

                throw new JsonLDResponseException(
                    $e->getMessage(),
                    $e->getResponse()->getStatusCode(),
                    $e,
                    $errorResponse
                );
            }

            throw new JsonLDResponseException("Unknown error", 0, $e);
        }
    }

    /**
     * @param object $object
     * @return void
     * @throws JsonLDException
     */
    protected function isValidObjectOrException(object $object) : void
    {
        if (false === method_exists($object, 'getId')) {
            throw new JsonLDException('Cannot persist object without getId method');
        }
    }

    /**
     * @param string $id
     * @param string|null $unique
     * @return string
     */
    protected function cacheKey(string $id, ?string $unique = null) : string
    {
        return $unique ? "jsonld_{$unique}_{$id}" : "jsonld_{$id}";
    }

    /**
     * @param string $jsonContents
     * @param string $type
     * @param string $format
     * @return array|object
     * @throws JsonLDSerializationException
     */
    protected function deserialize(string $jsonContents, string $type = '', string $format = JsonLDEncoder::FORMAT)
    {
        try {
            return $this->_serializer->deserialize($jsonContents, $type, $format);
        } catch (Exception $e) {
            throw new JsonLDSerializationException($e->getMessage(), 0, $e);
        }
    }
}
