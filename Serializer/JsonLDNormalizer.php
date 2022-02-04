<?php

namespace Bookboon\JsonLDClient\Serializer;

use Bookboon\JsonLDClient\Client\JsonLDException;
use Bookboon\JsonLDClient\Client\JsonLDSerializationException;
use Bookboon\JsonLDClient\Mapping\MappingCollection;
use Bookboon\JsonLDClient\Models\ApiError;
use Bookboon\JsonLDClient\Models\ApiErrorResponse;
use Bookboon\JsonLDClient\Models\ApiSource;
use DateTime;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class JsonLDNormalizer implements ContextAwareDenormalizerInterface, ContextAwareNormalizerInterface
{
    private MappingCollection $collection;
    private ObjectNormalizer $normalizer;
    private JsonLDCircularReferenceHandler $circularReferenceHandler;

    public function __construct(
        ObjectNormalizer $normalizer,
        MappingCollection $collection
    ) {
        $this->normalizer = $normalizer;
        $this->collection = $collection;
        $this->circularReferenceHandler = new JsonLDCircularReferenceHandler();
    }

    public function normalize($object, $format = null, array $context = [])
    {
        $context[ObjectNormalizer::CIRCULAR_REFERENCE_HANDLER] =
            $context[ObjectNormalizer::CIRCULAR_REFERENCE_HANDLER] ??
            $this->circularReferenceHandler;

        /* Check if we're dealing with multiple objects */
        if (is_array($object)) {
            $returnArray = [];

            foreach ($object as $item) {
                $returnArray[] = $this->normalizeItem($item, $format, $context);
            }

            return $returnArray;
        }

        return $this->normalizeItem($object, $format, $context);
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
            in_array($type, [ApiErrorResponse::class, ApiError::class. '[]'], true) ||
            ($data === [] && substr($type, -2) === '[]');
    }

    public function denormalize($data, $type, $format = null, array $context = [])
    {
        /* Check if we're dealing with multiple objects */
        if ($this->isDataArray($data)) {
            $returnArray = [];

            foreach ($data as $item) {
                if ($type === ApiError::class . '[]') {
                    $item['@type'] = 'ApiError';
                }
                if (isset($item['source'])) {
                    $item['source']['@type'] = 'ApiSource';
                }

                $returnArray[] = $this->denormalizeItem($item, $format, $context);
            }

            return $returnArray;
        }

        if ($type === ApiErrorResponse::class) {
            $data['@type'] = 'ApiErrorResponse';
        }

        return $this->denormalizeItem($data, $format, $context);
    }

    /**
     * @param mixed $data
     * @param mixed $format
     * @param array $context
     * @return bool|float|int|mixed|object|string
     * @throws JsonLDSerializationException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
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

        try {
            $endpoint = $this->collection->findEndpointByClass($class);
            $data = $endpoint->denormaliseData($data);
        } catch (JsonLDException $e) {
            // do nothing
        }

        return $this->normalizer->denormalize($data, $class, $format, $context);
    }

    /**
     * @param mixed $data
     * @param mixed $format
     * @param array $context
     * @return array|bool|float|int|string
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    protected function normalizeItem($data, $format, array $context)
    {
        if (is_scalar($data)) {
            return $data;
        }

        $shortClassFn = function (string $className) {
            if (false !== $pos = strrpos($className, '\\')) {
                return substr($className, $pos + 1);
            }
            return $className;
        };

        $result = $this->normalizer->normalize($data, $format, $context);

        if (is_object($data) && is_array($result)) {
            try {
                $endpoint = $this->collection->findEndpointByClass(get_class($data));
                $result = $endpoint->normaliseData($result);
            } catch (JsonLDException $e) {
                // do nothing
            }
        }

        if ($data instanceof \stdClass) {
            return is_array($result) ? $result : [$result];
        }

        return array_merge(
            ['@type' => $shortClassFn(get_class($data))],
            is_array($result) ? $result : [$result]
        );
    }

    protected function isDataArray($data) : bool
    {
        return is_array($data) && (count($data) === 0 || (array_keys($data) === range(0, count($data) - 1)));
    }
}
