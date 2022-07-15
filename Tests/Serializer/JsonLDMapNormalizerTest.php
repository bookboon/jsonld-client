<?php

namespace Bookboon\JsonLDClient\Tests\Serializer;

use ArrayObject;
use Bookboon\JsonLDClient\Mapping\MappingCollection;
use Bookboon\JsonLDClient\Mapping\MappingEndpoint;
use Bookboon\JsonLDClient\Serializer\JsonLDEncoder;
use Bookboon\JsonLDClient\Serializer\JsonLDMapNormalizer;
use Bookboon\JsonLDClient\Serializer\JsonLDNormalizer;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\MapHolderClass;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\MapValue;
use Bookboon\JsonLDClient\Tests\Fixtures\SerializerHelper;
use DateTime;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class JsonLDMapNormalizerTest extends TestCase
{
    public function testSerializeAndDeserializeClassesContainingEmptyEmbeddedMaps(): void
    {
        $serializer = SerializerHelper::create([
            new MappingEndpoint(MapHolderClass::class, '/api/v1/holder', [
                'value' => '@MapHolderClass'
            ])
        ]);

        $mapHolderClass = new MapHolderClass;
        $mapValue = new MapValue;
        $mapValue->setRules(new ArrayObject([]));
        $mapHolderClass->setMap(new ArrayObject(["example_key" => $mapValue]));

        $serializedJson = $serializer->serialize($mapHolderClass, JsonLDEncoder::FORMAT);
        $expectedJson = '{"@type":"MapHolderClass","map":{"example_key":{"@type":"MapValue","rules":{}}}}';
        $this->assertEquals($expectedJson, $serializedJson);


        /** @var MapHolderClass $mapHolderClass */
        $deserializedHolderClass = $serializer->deserialize(
            $serializedJson,
            '',
            JsonLDEncoder::FORMAT,
            [
                JsonLDNormalizer::MAPPPING_KEY => new MappingEndpoint(MapHolderClass::class, 'http://localhost/blah')
            ]
        );

        $this->assertEquals($mapHolderClass->getMap(), $deserializedHolderClass->getMap());
    }

    public function testSerializeAndDeserializeClassesContainingEmbeddedMapsWithValues(): void
    {
        $serializer = SerializerHelper::create([
            new MappingEndpoint(MapHolderClass::class, '/api/v1/holder', [
                'value' => '@MapHolderClass'
            ])
        ]);

        $mapHolderClass = new MapHolderClass;
        $mapValue = new MapValue;
        $mapValue->setRules(new ArrayObject(["ids" => ['id1', 'id2']]));
        $mapHolderClass->setMap(new ArrayObject(["example_key" => $mapValue]));

        $serializedJson = $serializer->serialize($mapHolderClass, JsonLDEncoder::FORMAT);
        $expectedJson = '{"@type":"MapHolderClass","map":{"example_key":{"@type":"MapValue","rules":{"ids":["id1","id2"]}}}}';
        $this->assertEquals($expectedJson, $serializedJson);


        /** @var MapHolderClass $mapHolderClass */
        $deserializedHolderClass = $serializer->deserialize(
            $serializedJson,
            '',
            JsonLDEncoder::FORMAT,
            [
                JsonLDNormalizer::MAPPPING_KEY => new MappingEndpoint(MapHolderClass::class, 'http://localhost/blah')
            ]
        );

        $this->assertEquals($mapHolderClass->getMap(), $deserializedHolderClass->getMap());
    }

    public function testSupportsMethodReturnsFalseWhenDataIsNotArray(): void
    {
        $jsonLDMapNormalizer = new JsonLDMapNormalizer(new ObjectNormalizer(), new MappingCollection([], []));

        $this->assertFalse($jsonLDMapNormalizer->supportsDenormalization(new DateTime(), ''), "types that are not arrays should return false");
    }

    public function testSupportsMethodReturnsFalseWhenFormatIsNotJsonLD(): void
    {
        $jsonLDMapNormalizer = new JsonLDMapNormalizer(new ObjectNormalizer(), new MappingCollection([], []));

        $this->assertFalse($jsonLDMapNormalizer->supportsDenormalization([], '', 'json'), "if format is json should return false");
        $this->assertFalse($jsonLDMapNormalizer->supportsNormalization(new ArrayObject(), 'json'), "if format is json should return false");
    }

    public function testSupportsMethodReturnsTrueWhenFormatIsNull(): void
    {
        $jsonLDMapNormalizer = new JsonLDMapNormalizer(new ObjectNormalizer(), new MappingCollection([], []));

        $this->assertTrue($jsonLDMapNormalizer->supportsDenormalization([], ''), "if format is null should support denormalisation");
        $this->assertFalse($jsonLDMapNormalizer->supportsNormalization(new ArrayObject()), "if format is null should not support normalisation of object");
    }

    public function testSupportsMethodReturnsTrueWhenFormatIsJsonLD(): void
    {
        $jsonLDMapNormalizer = new JsonLDMapNormalizer(new ObjectNormalizer(), new MappingCollection([], []));

        $this->assertTrue($jsonLDMapNormalizer->supportsDenormalization([], '', 'json-ld'), "if format is json-ld should support denormalisation");
        $this->assertTrue($jsonLDMapNormalizer->supportsNormalization(new ArrayObject(), 'json-ld'), "if format is json-ld should support normalisation");
    }

    public function testSupportsMethodReturnsTrueWhenMapValuesHaveTypePropertyDefined(): void
    {
        $jsonLDMapNormalizer = new JsonLDMapNormalizer(new ObjectNormalizer(), new MappingCollection([], []));

        $exampleMap = [
            "key1" => [
                "@type" => 'key1value'
            ],
            "key2" => [
                "@type" => 'key2value'
            ],
            "key3" => [
                "@type" => 'key3value'
            ]
        ];

        $this->assertTrue($jsonLDMapNormalizer->supportsDenormalization($exampleMap, ''), "types that have the @type property defined in the map value should return true");
    }
}
