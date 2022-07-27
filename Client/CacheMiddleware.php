<?php

namespace Bookboon\JsonLDClient\Client;

use Bookboon\JsonLDClient\Models\ConstantBufferStream;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7\BufferStream;
use GuzzleHttp\Psr7\PumpStream;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

class CacheMiddleware
{
    public const USE_CACHE = 'BOOKBOON_USE_CACHE';
    private const CACHE_TIME = 1800;

    private CacheInterface $_cache;

    protected array $httpMethods = ['GET' => true];
    protected array $safeMethods = ['GET' => true, 'HEAD' => true, 'OPTIONS' => true, 'TRACE' => true];

    public function __construct(CacheInterface $cache)
    {
        $this->_cache = $cache;
    }

    private static function sameHeaders(array $old, array $new): bool
    {
        $c = count($old);
        if ($c != count($new)) {
            return false;
        }
        for ($i = 0; $i < $c; ++$i) {
            if ($old[$i] != $new[$i]) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param callable $handler
     *
     * @return callable
     */
    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use (&$handler) {
            $useCache = $options[self::USE_CACHE] ?? false;

            if (!$useCache) {
                return $handler($request, $options);
            }

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

            return $handler($request, $options)->then(
                function (ResponseInterface $response) use ($request) {
                    return $this->addToCache($request, $response, self::CACHE_TIME);
                }
            );
        };
    }

    protected function fetchCache(RequestInterface $request) : ?ResponseInterface
    {
        $resp = null;
        $preCacheInfo = $this->_cache->get($this->preCacheKey($request));
        $varyHeaders = $preCacheInfo['headers'] ?? [];
        /** @var \DateTime $headerTime */
        $headerTime = $preCacheInfo['timestamp'] ?? new \DateTime();

        $raw = $this->_cache->get($this->cacheKey($request, $varyHeaders));

        if ($raw !== null) {
            $resp = $raw['response'];
            /** @var \DateTime $respTime */
            $respTime = $raw['timestamp'];
            if ($respTime < $headerTime) {
                // if the headers were updated before this cache entry was created, that means that either:
                // 1. the content of the vary header has changed, or
                // 2. someone deliberately cleared the cache item containing the header (to signal cache invalidation)
                // in either case, the correct thing to do is to not use this cache item
                return null;
            }
        }

        return $resp instanceof ResponseInterface ? $resp : null;
    }

    protected function addToCache(RequestInterface $request, ResponseInterface $response, int $ttl) : ResponseInterface
    {
        $body = $response->getBody();

        // If the body is not seekable, we have to replace it by a seekable one
        if (!$body->isSeekable()) {
            $response = $response->withBody(
                Utils::streamFor($body->getContents())
            );
        }

        $bodyText = (string)$response->getBody();

        $cacheResponse = $response->withBody(
            new ConstantBufferStream($bodyText),
        );

        $vary = $response->getHeaderLine("Vary");
        $varyHeaders = [];

        if (!empty($vary)) {
            $varyHeaders = explode(", ", $vary);
            natsort($varyHeaders);
        }

        $cachedHeaders = $this->_cache->get($this->preCacheKey($request));

        if ($cachedHeaders == null || !self::sameHeaders($cachedHeaders['headers'] ?? [], $varyHeaders)) {
            $this->_cache->set(
                $this->preCacheKey($request),
                [
                    'headers' => $varyHeaders,
                    'timestamp' => new \DateTimeImmutable(),
                ],
                $ttl,
            );
        }

        $this->_cache->set(
            $this->cacheKey($request, $varyHeaders),
            [
                'response' => $cacheResponse,
                'timestamp' => new \DateTimeImmutable(),
            ],
            $ttl
        );

        // always rewind back to the start otherwise other middlewares may get empty "content"
        if ($body->isSeekable()) {
            $response->getBody()->rewind();
        }

        return $response;
    }

    protected function preCacheKey(RequestInterface $request): string
    {
        $id = str_replace('/', '--',  trim($request->getUri()->getPath(), '/ '));
        return "jsonld_$id";
    }

    protected function cacheKey(RequestInterface $request, array $vary): string
    {
        $hashBasis = [$request->getUri()->getPath(), $request->getUri()->getQuery()];
        foreach ($vary as $vValue) {
            $hashBasis[] = $request->getHeaderLine($vValue);
        }

        $hash = sha1(implode('\n', $hashBasis));
        return $this->preCacheKey($request) .  "_{$hash}";
    }

    private function invalidateCache(RequestInterface $request, ResponseInterface $response) : ResponseInterface
    {
        $this->_cache->delete($this->preCacheKey($request));
        return $response;
    }
}
