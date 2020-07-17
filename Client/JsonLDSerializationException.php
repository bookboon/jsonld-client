<?php

namespace Bookboon\JsonLDClient\Client;

use Throwable;

class JsonLDSerializationException extends JsonLDException
{
    /**
     * JsonLDSerializationException constructor.
     * @param string $message
     * @param int $statusCode
     * @param Throwable|null $previous
     */
    public function __construct(string $message, int $statusCode, Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
    }
}
