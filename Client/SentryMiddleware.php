<?php

namespace Bookboon\JsonLDClient\Client;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Sentry\SentrySdk;
use Sentry\Tracing\SpanContext;

class SentryMiddleware
{
    public const SENTRY_HEADER = 'sentry-trace';

    public function __invoke(callable $handler): callable {
        return function (RequestInterface $request, array $options) use (&$handler) {
            $transaction = null;
            $span = null;

            if (class_exists(SentrySdk::class)) {
                $transaction = SentrySdk::getCurrentHub()->getTransaction();
            }

            if ($transaction) {
                $ctx = new SpanContext();
                $ctx->setOp($transaction->getOp());
                $ctx->setTags([
                    "http.flavor" => $request->getProtocolVersion(),
                    "http.method" => $request->getMethod(),
                    "http.url" => (string)$request->getUri(),
                    "net.host.name" => $request->getUri()->getHost(),
                    "net.user-agent" => $request->getHeaderLine('User-Agent'),
                ]);
                $body = '';
                $body_too_large = false;
                if ($request->getBody()->getSize() < 4096) {
                    if (!str_contains($request->getHeaderLine("Content-Encoding"), "gzip")) {
                        $body = $request->getBody()->getContents();
                    } else if (\extension_loaded('zlib')) {
                        $body = gzdecode($request->getBody()->getContents());
                    }
                } else {
                    $body_too_large = true;
                }

                $bareUri = $request->getUri()->withQuery('');
                $ctx->setData([
                    'http.request.method' => $request->getMethod(),
                    'url' => (string)$bareUri,
                    'http.query' => '?' . $request->getUri()->getQuery(),
                    'body' => $body,
                    'body.too_large' => $body_too_large,
                ]);
                $span = $transaction->startChild($ctx);
                $request = $request->withHeader(self::SENTRY_HEADER, $span->toTraceparent());
            }

            return $handler($request, $options)->then(function (ResponseInterface $response) use ($request, $span) {
                if (isset($span)) {
                    $span->setHttpStatus($response->getStatusCode());
                    $data = $span->getData();
                    $data['http.response.status_code'] = $response->getStatusCode();
                    $data['http.response_content_length'] = $response->getBody()->getSize();

                    $span->setData($data);
                    $span->finish();
                }
                return $response;
            });
        };
    }
}