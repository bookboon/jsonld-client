<?php

namespace Bookboon\JsonLDClient\Client;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class GuzzleClientFactory
{
    const USER_AGENT = 'JsonLDClient/0.3';

    public static function create(RequestStack $requestFactory) : ClientInterface
    {
        return new Client(
            [
                'headers' => array_merge(
                    self::getTracingHeaders($requestFactory),
                    [
                    'User-Agent' => self::USER_AGENT
                    ]
                )
            ]
        );
    }

    private static function getTracingHeaders(RequestStack $requestFactory): array
    {
        $headers = [];
        $request = $requestFactory->getMasterRequest();

        if ($request !== null) {
            foreach ($request->headers as $key => $value) {
                if (stripos($key, 'x-b3-') !== false || stripos($key, 'x-request-id') !== false) {
                    $headers[$key] = implode('', $value);
                }
            }
        }

        return $headers;
    }
}
