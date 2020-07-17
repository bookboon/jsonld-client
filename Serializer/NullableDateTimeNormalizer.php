<?php

namespace Bookboon\JsonLDClient\Serializer;

use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

class NullableDateTimeNormalizer extends DateTimeNormalizer
{
    /**
     * @param mixed $data
     * @param string $class
     * @param string|null $format
     * @param array $context
     * @return array|\DateTime|\DateTimeImmutable|false|object|null
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if ($data === null) {
            return null;
        }

        return parent::denormalize($data, $class, $format, $context);
    }
}
