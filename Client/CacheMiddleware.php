<?php

namespace Bookboon\JsonLDClient\Client;

use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7\PumpStream;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

class CacheMiddleware
{
    public const USE_CACHE = 'USE_CACHE';
    private const CACHE_TIME = 1800;

    private CacheInterface $_cache;

    protected array $httpMethods = ['GET' => true];
    protected array $safeMethods = ['GET' => true, 'HEAD' => true, 'OPTIONS' => true, 'TRACE' => true];

    public function __construct(CacheInterface $cache)
    {
        $this->_cache = $cache;
    }

    /**
     * @param callable $handler
     *
     * @return callable
     */
    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use (&$handler) {
            if (!isset($this->httpMethods[strtoupper($request->getMethod())])) {
                // No caching for this method allowed

                return $handler($request, $options)->then(
                    function (ResponseInterface $response) use ($request) {
                        if (!isset($this->safeMethods[$request->getMethod()])) {
                            // Invalidate cache after a call of non-safe method on the same URI
                            $response = $this->invalidateCache($request, $response);
                        }

                        return $response;
                    }
                );
            }

            $cacheResponse = $this->fetchCache($request);
            if ($cacheResponse !== null) {
                return new FulfilledPromise(
                    $cacheResponse
                );
            }

            /** @var Promise $promise */
            $promise = $handler($request, $options);

            return $promise->then(
                function (ResponseInterface $response) use ($request) {
                    return $this->addToCache($request, $response);
                }
            );
        };
    }

    protected function fetchCache(RequestInterface $request) : ?ResponseInterface
    {
        // TODO: implement this
        $cacheKey = 'test';
        $raw = $this->_cache->get($this->cacheKey($request, []));
        if ($raw !== null) {
            str
//            $serialised = json_decode($this->_cache->get($cacheKey, null),
//                true,
//                512,
//                JSON_THROW_ON_ERROR
//            );
            return \GuzzleHttp\Psr7\parse_response($raw);

        }
        return null;
    }

    protected function addToCache(RequestInterface $request, ResponseInterface $response, int $ttl) : ResponseInterface
    {
        $body = $response->getBody();

        // If the body is not seekable, we have to replace it by a seekable one
        if (!$body->isSeekable()) {
            $response = $response->withBody(
                \GuzzleHttp\Psr7\Utils::streamFor($body->getContents())
            );
        }

        $cacheResponse = $this->response->withBody(
            new PumpStream(
                new BodyStore($body),
                [
                    'size' => mb_strlen($body),
                ]
            )
        );

        $this->_cache->set(
            $this->cacheKey($request, []),
            $response,
            $ttl
        );

        // always rewind back to the start otherwise other middlewares may get empty "content"
        if ($body->isSeekable()) {
            $response->getBody()->rewind();
        }

        return $response;
    }

    protected function cacheKey(RequestInterface $request, array $vary): string
    {
        $id = str_replace('/', '--', $request->getUri()->getPath());
        // TODO: Proper sha1 based on Vary header from existing cache;

        $hashBasis = $request->getUri()->getPath() . $request->getUri()->getQuery();
        foreach ($vary as $vValue) {
            $hashBasis .= $request->getHeaderLine($vValue);
        }

        $hash = sha1($hashBasis);
        return "jsonld_{$id}_{$hash}";
    }

    private function invalidateCache(RequestInterface $request, ResponseInterface $response) : ResponseInterface
    {
        $this->_cache->delete($this->cacheKey($request, []))
        return $response;
    }
}
