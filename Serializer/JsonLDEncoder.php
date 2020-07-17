<?php

namespace Bookboon\JsonLDClient\Serializer;

use Symfony\Component\Serializer\Encoder\JsonEncoder;

class JsonLDEncoder extends JsonEncoder
{
    const FORMAT = 'json-ld';

    public function supportsEncoding($format)
    {
        return self::FORMAT === $format;
    }

    public function supportsDecoding($format)
    {
        return self::FORMAT === $format;
    }
}
