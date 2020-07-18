<?php

namespace Bookboon\JsonLDClient\Serializer;

use Bookboon\JsonLDClient\Client\JsonLDSerializationException;
use Bookboon\JsonLDClient\Mapping\MappingCollection;
use Bookboon\JsonLDClient\Models\ApiError;
use Bookboon\JsonLDClient\Models\ApiErrorResponse;
use DateTime;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class JsonLDNormalizer implements ContextAwareDenormalizerInterface, ContextAwareNormalizerInterface
{
    private $collection;
    private $normalizer;
    private $circularReferenceHandler;

    public function __construct(
        ObjectNormalizer $normalizer,
        MappingCollection $collection
    ) {
        $this->normalizer = $normalizer;
        $this->collection = $collection;
        $this->circularReferenceHandler = new JsonLDCircularReferenceHandler();
    }

    public function normalize($data, $format = null, array $context = [])
    {
        $context[ObjectNormalizer::CIRCULAR_REFERENCE_HANDLER] =
            $context[ObjectNormalizer::CIRCULAR_REFERENCE_HANDLER] ??
            $this->circularReferenceHandler;

        /* Check if we're dealing with multiple objects */
        if (is_array($data)) {
            $returnArray = [];

            foreach ($data as $item) {
                $returnArray[] = $this->normalizeItem($item, $format, $context);
            }

            return $returnArray;
        }

        return $this->normalizeItem($data, $format, $context);
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return (
            (is_object($data) && $format === JsonLDEncoder::FORMAT)
            || $this->isDataArray($data)
            )
            && !($data instanceof DateTime
            );
    }

    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return isset($data['@type']) ||
            isset($data[0]['@type']) ||
            in_array($type, [ApiErrorResponse::class, ApiError::class. '[]'], true);
    }

    public function denormalize($data, $class, $format = null, array $context = [])
    {
        /* Check if we're dealing with multiple objects */
        if ($this->isDataArray($data)) {
            $returnArray = [];

            foreach ($data as $item) {
                if ($class === ApiError::class . '[]') {
                    $item['@type'] = 'ApiError';
                }

                $returnArray[] = $this->denormalizeItem($item, $format, $context);
            }

            return $returnArray;
        }

        if ($class === ApiErrorResponse::class) {
            $data['@type'] = 'ApiErrorResponse';
        }

        return $this->denormalizeItem($data, $format, $context);
    }

    protected function denormalizeItem($data, $format, array $context)
    {
        if (is_scalar($data)) {
            return $data;
        }

        $attemptType = $data['@type'];
        $class = $this->collection->findClassByShortNameOrDefault($attemptType);

        if (false === class_exists($class)) {
            throw new JsonLDSerializationException('Cannot find class: ' . $attemptType, 0, null);
        }

        return $this->normalizer->denormalize($data, $class, $format, $context);
    }

    protected function normalizeItem($data, $format, array $context)
    {
        if (is_scalar($data)) {
            return $data;
        }

        return array_merge(
            ['@type' => substr(get_class($data), strrpos(get_class($data), '\\') + 1)],
            $this->normalizer->normalize($data, $format, $context)
        );
    }

    protected function isDataArray($data) : bool
    {
        return is_array($data) && (count($data) === 0 || (array_keys($data) === range(0, count($data) - 1)));
    }
}
