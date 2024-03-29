<?php

namespace Bookboon\JsonLDClient\Client;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Utils;
use Psr\SimpleCache\CacheInterface;
use Sentry\SentrySdk;
use Symfony\Component\HttpFoundation\RequestStack;
use function GuzzleHttp\choose_handler;

class GuzzleClientFactory
{
    const USER_AGENT = 'JsonLDClient/2.2';

    public static function createStack(
        ?CacheInterface $cache
    ): HandlerStack {
        if (method_exists(Utils::class, 'chooseHandler')) {
            $handler = new HandlerStack(Utils::chooseHandler());
        } else {
            $handler = new HandlerStack(choose_handler());
        }

        if ($cache) {
            $handler->push(new CacheMiddleware($cache));
        }

       $handler->push(new SentryMiddleware());

        /** @psalm-suppress InvalidArgument this is needed because Guzzle's type annotation game is not fresh enough */
        return HandlerStack::create($handler);
    }

    public static function create(
        RequestStack $requestFactory,
        HandlerStack $stack,
        float $defaultTimeout = 30.0
    ) : ClientInterface {
        return new Client(
            [
                'headers' => array_merge(
                    self::getTracingHeaders($requestFactory),
                    [
                    'User-Agent' => self::USER_AGENT
                    ]
                ),
                'handler' => $stack,
                'timeout'  => $defaultTimeout
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
                    || stripos($key, 'sentry-trace') !== false
                ) {
                    $headers[$key] = implode('', $value);
                }
            }
        }

        return $headers;
    }
}
