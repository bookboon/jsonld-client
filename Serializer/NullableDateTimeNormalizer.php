<?php

namespace Bookboon\JsonLDClient\Serializer;

use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

class NullableDateTimeNormalizer extends DateTimeNormalizer
{
    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @param array $context
     * @return null|\DateTimeInterface
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        if ($data === null) {
            return null;
        }

        return parent::denormalize($data, $type, $format, $context);
    }
}
