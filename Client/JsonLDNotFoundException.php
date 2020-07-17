<?php

namespace Bookboon\JsonLDClient\Client;

class JsonLDNotFoundException extends JsonLDException
{
    public function __construct()
    {
        parent::__construct("404: Not found", 404);
    }
}
