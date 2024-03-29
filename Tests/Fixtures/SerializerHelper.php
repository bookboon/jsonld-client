<?php


namespace Bookboon\JsonLDClient\Tests\Fixtures;


use Bookboon\JsonLDClient\Mapping\MappingApi;
use Bookboon\JsonLDClient\Mapping\MappingCollection;
use Bookboon\JsonLDClient\Mapping\MappingEndpoint;
use Bookboon\JsonLDClient\Serializer\JsonLDEncoder;
use Bookboon\JsonLDClient\Serializer\JsonLDMapNormalizer;
use Bookboon\JsonLDClient\Serializer\JsonLDNormalizer;
use Bookboon\JsonLDClient\Serializer\NullableDateTimeNormalizer;
use Bookboon\JsonLDClient\Serializer\StdClassNormalizer;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class SerializerHelper
{
    /**
     * @param array<MappingEndpoint> $mappings
     * @param array $defaultContext
     * @param array<MappingApi> $apiRoot
     * @return SerializerInterface
     * @throws \Bookboon\JsonLDClient\Mapping\MappingException
     */
    public static function create(array $mappings = [], array $defaultContext = [], array $apiRoot = []) : SerializerInterface
    {
        $docblockExtractor = new PhpDocExtractor();
        $reflectionExtractor = new ReflectionExtractor();
        /** @psalm-suppress TooManyArguments */
        $propertyExtractor = new PropertyInfoExtractor(
            [
            ],
            [
                $docblockExtractor,
                $reflectionExtractor
            ]
        );

        $propertyAccessor = new PropertyAccessor();

        $collection = new MappingCollection($mappings, $apiRoot);
        $normalizer = new ObjectNormalizer(null, null, $propertyAccessor, $propertyExtractor, null, null, $defaultContext);
        $serializer = new Serializer(
            [
                new JsonLDNormalizer($normalizer, $collection),
                new JsonLDMapNormalizer($normalizer, $collection),
                new NullableDateTimeNormalizer(new DateTimeNormalizer()),
                new StdClassNormalizer(),
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
