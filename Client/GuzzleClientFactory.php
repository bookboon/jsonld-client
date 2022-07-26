<?php

namespace Bookboon\JsonLDClient\Client;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Utils;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class GuzzleClientFactory
{
    const USER_AGENT = 'JsonLDClient/2.2';

    public static function createStack(
        ?CacheInterface $cache
    ): HandlerStack {
        $handler = new HandlerStack(Utils::chooseHandler());
        if ($cache) {
            $handler->push(new CacheMiddleware($cache));
        }

        /** @psalm-suppress InvalidArgument this is needed because Guzzle's type annotation game is not fresh enough */
        return HandlerStack::create($handler);
    }

    public static function create(
        RequestStack $requestFactory,
        HandlerStack $stack,
    ) : ClientInterface {
        return new Client(
            [
                'headers' => array_merge(
                    self::getTracingHeaders($requestFactory),
                    [
                    'User-Agent' => self::USER_AGENT
                    ]
                ),
                'handler' => $stack
            ]
        );
    }

    private static function getTracingHeaders(RequestStack $requestFactory): array
    {
        $headers = [];
        $request = $requestFactory->getMainRequest();

        if ($request !== null) {
            foreach ($request->headers as $key => $value) {
                if (stripos($key, 'x-b3-') !== false
                    || stripos($key, 'x-request-id') !== false
                ) {
                    $headers[$key] = implode('', $value);
                }
            }
        }

        return $headers;
    }
}
