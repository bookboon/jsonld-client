<?php

namespace Bookboon\JsonLDClient\Serializer;

use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use DateTimeInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class NullableDateTimeNormalizer implements DenormalizerInterface
{
    private DateTimeNormalizer $dateTimeNormalizer;

    public function __construct(DateTimeNormalizer $dateTimeNormalizer)
    {
        $this->dateTimeNormalizer = $dateTimeNormalizer;
    }

    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @param array $context
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function denormalize($data, $type, $format = null, array $context = []) : ?DateTimeInterface
    {
        if ($data === null) {
            return null;
        }

        return $this->dateTimeNormalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null) : bool
    {
       return $this->dateTimeNormalizer->supportsDenormalization($data, $type, $format);
    }
}
