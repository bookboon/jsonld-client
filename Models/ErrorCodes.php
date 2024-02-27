<?php

namespace Bookboon\JsonLDClient\Models;

class ErrorCodes
{
    public const TOKEN_NOT_FOUND_ERR = 'AS10001';
    public const TOKEN_INVALID_ERR = 'AS10002';

    public static function isTokenError(?string $test): bool
    {

        return $test !== null && in_array($test, [self::TOKEN_INVALID_ERR, self::TOKEN_NOT_FOUND_ERR]);
    }
}
