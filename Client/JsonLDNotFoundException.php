<?php

namespace Bookboon\JsonLDClient\Client;

use Throwable;

class JsonLDNotFoundException extends JsonLDException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct("404: Not found", 404, $previous);
    }
}
