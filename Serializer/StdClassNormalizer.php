<?php

namespace Bookboon\JsonLDClient\Serializer;

use ArrayObject;
use stdClass;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Necessary as the serializer does not support object type hint: https://github.com/symfony/symfony/issues/42226
 */
class StdClassNormalizer implements DenormalizerInterface, NormalizerInterface
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

    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return $type === stdClass::class;
    }

    public function normalize($object, $format = null, array $context = [])
    {
        $arrayToNormalize = is_array($object) ? $object : (array) $object;

        if (empty($arrayToNormalize)) {
            // ArrayObject ensure empty json object {}
            return new ArrayObject($arrayToNormalize);
        }

        return $arrayToNormalize;
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof stdClass;
    }
}
