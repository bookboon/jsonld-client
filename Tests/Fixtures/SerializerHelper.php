<?php


namespace Bookboon\JsonLDClient\Tests\Fixtures;


use Bookboon\JsonLDClient\Mapping\MappingCollection;
use Bookboon\JsonLDClient\Serializer\JsonLDEncoder;
use Bookboon\JsonLDClient\Serializer\JsonLDNormalizer;
use Bookboon\JsonLDClient\Serializer\NullableDateTimeNormalizer;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class SerializerHelper
{
    public static function create(array $mappings = [], array $defaultContext = []) : SerializerInterface
    {
        $propertyExtractor = new PhpDocExtractor();
        $collection = new MappingCollection($mappings, 'Bookboon\JsonLDClient\Tests\Fixtures\Models');
        $normalizer = new ObjectNormalizer(null, null, null, $propertyExtractor, null, null, $defaultContext);
        $serializer = new Serializer(
            [
                new JsonLDNormalizer($normalizer, $collection),
                new NullableDateTimeNormalizer()
            ],
            [
                new JsonLDEncoder(),
                new JsonDecode([JsonDecode::ASSOCIATIVE => true])
            ]
        );

        $normalizer->setSerializer($serializer);

        return $serializer;
    }
}
