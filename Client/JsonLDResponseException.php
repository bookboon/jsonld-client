<?php

namespace Bookboon\JsonLDClient\Client;

use Bookboon\JsonLDClient\Models\ApiErrorResponse;
use Throwable;

class JsonLDResponseException extends JsonLDException
{
    protected ?ApiErrorResponse $response;

    /**
     * JsonLDResponseException constructor.
     * @param string $message
     * @param integer $statusCode
     * @param Throwable|null $previous
     * @param ApiErrorResponse|null $response
     */
    public function __construct(
        string $message,
        int $statusCode,
        Throwable $previous = null,
        ?ApiErrorResponse $response = null
    ) {
        $this->response = $response;

        if (null !== $response) {
            $message = (string) $response;
        }

        parent::__construct($message, $statusCode, $previous);
    }
}
