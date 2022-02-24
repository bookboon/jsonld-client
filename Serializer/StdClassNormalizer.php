<?php

namespace Bookboon\JsonLDClient\Serializer;

use stdClass;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class StdClassNormalizer implements DenormalizerInterface
{
    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @param array $context
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        if ($data === null) {
            return null;
        }

        return  (object) $data;
    }

    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return $type === stdClass::class;
    }
}
