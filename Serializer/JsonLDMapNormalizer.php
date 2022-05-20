<?php

namespace Bookboon\JsonLDClient\Serializer;

use ArrayObject;

class JsonLDMapNormalizer extends JsonLDNormalizer
{
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        foreach ($data as $key => $value) {
            if (isset($value['@type'])) {
                $data[$key] = $this->denormalizeItem($value, $format, $context);
            } else {
                $data[$key] = $value;
            }

        }

        return new ArrayObject($data);
    }

    public function normalize($object, $format = null, array $context = [])
    {
        $data = [];
        foreach ($object as $key => $value) {
            if (is_object($value)) {
                $data[$key] = $this->normalizeItem($value, $format, $context);
            } else {
                $data[$key] = $value;
            }
        }

        return count($data) === 0 ? new ArrayObject($data) : $data;
    }

    /**
     * Supports map jsonld types for example
     *  [
     *   $exampleMap = [
     *       "key1" => [
     *           "@type" => 'key1value'
     *        ],
     *        "key2" => [
     *           "@type" => 'key2value'
     *       ],
     *      "key3" => [
     *           "@type" => 'key3value'
     *       ]
     *  ];
     *
     * @param $data
     * @param $type
     * @param $format
     * @param array $context
     * @return bool
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return $this->isDataArray($data);
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof ArrayObject;
    }

    protected function isDataArray($data) : bool
    {
        return is_array($data) && !isset($data["@type"]) && (count($data) === 0 || !(array_keys($data) === range(0, count($data) - 1)));
    }
}
