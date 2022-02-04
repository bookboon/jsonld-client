<?php

namespace Bookboon\JsonLDClient\Tests\Serializer;

use Bookboon\JsonLDClient\Client\JsonLDException;
use Bookboon\JsonLDClient\Client\JsonLDSerializationException;
use Bookboon\JsonLDClient\Mapping\MappingEndpoint;
use Bookboon\JsonLDClient\Serializer\JsonLDEncoder;
use Bookboon\JsonLDClient\Serializer\JsonLDNormalizer;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\CircularChild;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\CircularParent;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\CircularParentWithId;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\DatedClass;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\DynamicArrayClass;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\NestedArrayClass;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\NestedClass;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\NestedClassWithoutDoc;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\SimpleClass;
use Bookboon\JsonLDClient\Tests\Fixtures\SerializerHelper;
use DateTime;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

class JsonLDNormalizerTest extends TestCase
{
    public function testSimpleDeserialize() : void
    {
        $serializer = SerializerHelper::create([]);
        $testJson = <<<JSON
        {
            "@type": "SimpleClass",
            "value": "some random string"
        }
        JSON;

        $object = $serializer->deserialize(
            $testJson,
            '',
            JsonLDEncoder::FORMAT,
            $this->getContextWithMapping(SimpleClass::class)
        );

        self::assertInstanceOf(SimpleClass::class, $object);
        self::assertEquals("some random string", $object->getValue());
    }

    public function testDeserializeWithMapping(): void
    {
        $serializer = SerializerHelper::create([
            new MappingEndpoint('Bookboon\JsonLDClient\Tests\Fixtures\Models\SimpleClass', '', [
                'value' => '@MangledValue'
            ])
        ]);

        $testJson = <<<JSON
        {
            "@type": "SimpleClass",
            "@MangledValue": "some random string"
        }
        JSON;

        $object = $serializer->deserialize($testJson, '', JsonLDEncoder::FORMAT);

        self::assertInstanceOf(SimpleClass::class, $object);
        self::assertEquals("some random string", $object->getValue());
    }

    public function testDatedDeserialize_HasDate(): void
    {
        $serializer = SerializerHelper::create([]);
        $date = DateTime::createFromFormat(DateTime::RFC3339_EXTENDED, "2014-01-01T23:28:56.782Z");

        $testJson = <<<JSON
        {
            "@type": "DatedClass",
            "created": "2014-01-01T23:28:56.782Z"
        }
        JSON;

        $object = $serializer->deserialize(
            $testJson,
            '',
            JsonLDEncoder::FORMAT,
            $this->getContextWithMapping(DatedClass::class)
        );

        self::assertInstanceOf(DatedClass::class, $object);
        self::assertEquals($date, $object->getCreated());
    }

    public function testDatedDeserialize_HasNullDate() : void
    {
        $serializer = SerializerHelper::create([]);
        $testJson = <<<JSON
        {
            "@type": "DatedClass",
            "created": null
        }
        JSON;

        $object = $serializer->deserialize(
            $testJson,
            '',
            JsonLDEncoder::FORMAT,
            $this->getContextWithMapping(DatedClass::class)
        );

        self::assertInstanceOf(DatedClass::class, $object);
        self::assertNull($object->getCreated());
    }

    public function testNestedDeserialize() : void
    {
        $serializer = SerializerHelper::create([]);
        $testJson = <<<JSON
        {
            "@type": "NestedClass",
            "string": "some random string",
            "simpleClass": {
                "@type": "SimpleClass",
                "value": "some other string"
            }
        }
        JSON;

        $object = $serializer->deserialize(
            $testJson,
            '',
            JsonLDEncoder::FORMAT,
            $this->getContextWithMapping(NestedClass::class)
        );
        self::assertInstanceOf(NestedClass::class, $object);
        self::assertEquals("some random string", $object->getString());

        $simple = $object->getSimpleClass();
        self::assertNotNull($simple);
        self::assertInstanceOf(SimpleClass::class, $simple);
        self::assertEquals('some other string', $simple->getValue());
    }

    public function testNestedWithoutDocDeserialize() : void
    {
        $serializer = SerializerHelper::create([]);
        $testJson = <<<JSON
        {
            "@type": "NestedClassWithoutDoc",
            "string": "some random string",
            "simpleClass": {
                "@type": "SimpleClass",
                "value": "some other string"
            }
        }
        JSON;

        $object = $serializer->deserialize(
            $testJson,
            '',
            JsonLDEncoder::FORMAT,
            $this->getContextWithMapping(NestedClassWithoutDoc::class)
        );

        self::assertInstanceOf(NestedClassWithoutDoc::class, $object);
        self::assertEquals("some random string", $object->getString());

        $simple = $object->getSimpleClass();
        self::assertNotNull($simple);
        self::assertInstanceOf(SimpleClass::class, $simple);
        self::assertEquals('some other string', $simple->getValue());
    }

    public function testNestedArrayDeserialize() : void
    {
        $serializer = SerializerHelper::create([]);
        $testJson = <<<JSON
        {
            "@type": "NestedArrayClass",
            "string": "some random string",
            "simpleClasses": [
                {
                    "@type": "SimpleClass",
                    "value": "some other string"
                },
                {
                    "@type": "SimpleClass",
                    "value": "different value"
                }
            ]
        }
        JSON;

        $object = $serializer->deserialize(
            $testJson,
            '',
            JsonLDEncoder::FORMAT,
            $this->getContextWithMapping(NestedArrayClass::class)
        );

        self::assertInstanceOf(NestedArrayClass::class, $object);
        self::assertEquals("some random string", $object->getString());
        self::assertIsArray($object->getSimpleClasses());
        self::assertCount(2, $object->getSimpleClasses());
        self::assertEquals('some other string', $object->getSimpleClasses()[0]->getValue());
        self::assertEquals('different value', $object->getSimpleClasses()[1]->getValue());
    }

    public function testEmptyNestedArrayDeserialize() : void
    {
        $serializer = SerializerHelper::create([]);
        $testJson = <<<JSON
        {
            "@type": "NestedArrayClass",
            "string": "some random string",
            "simpleClasses": [
            ]
        }
        JSON;

        $object = $serializer->deserialize(
            $testJson,
            '',
            JsonLDEncoder::FORMAT,
            $this->getContextWithMapping(NestedArrayClass::class)
        );
        
        self::assertInstanceOf(NestedArrayClass::class, $object);
        self::assertEquals("some random string", $object->getString());
        self::assertIsArray($object->getSimpleClasses());
        self::assertCount(0, $object->getSimpleClasses());
    }

    public function testArrayDeserialize() : void
    {
        $serializer = SerializerHelper::create([]);
        $testJson = <<<JSON
        [
            {
                "@type": "SimpleClass",
                "value": "some other string"
            },
            {
                "@type": "SimpleClass",
                "value": "different value"
            }
        ]
        JSON;

        $objects = $serializer->deserialize(
            $testJson,
            '',
            JsonLDEncoder::FORMAT,
            $this->getContextWithMapping(SimpleClass::class)
        );

        self::assertIsArray($objects);
        self::assertCount(2, $objects);
        self::assertEquals('some other string', $objects[0]->getValue());
        self::assertEquals('different value', $objects[1]->getValue());
    }

    public function testSimpleSerialize() : void
    {
        $serializer = SerializerHelper::create([]);
        $expectJson = '{"@type":"SimpleClass","value":"some other string","id":"232d2c41-278a-4377-bd9d-c6046494ceaf"}';
        $testObject = new SimpleClass();
        $testObject->setValue('some other string');

        $testJson = $serializer->serialize($testObject,JsonLDEncoder::FORMAT);

        self::assertEquals($expectJson, $testJson);
    }

    public function testSerializeWithMapping(): void
    {
        $serializer = SerializerHelper::create([
            new MappingEndpoint('Bookboon\JsonLDClient\Tests\Fixtures\Models\SimpleClass', '', [
                'value' => '@MangledValue'
            ])
        ]);

        $expectJson = '{"@type":"SimpleClass","id":"232d2c41-278a-4377-bd9d-c6046494ceaf","@MangledValue":"some other string"}';
        $testObject = new SimpleClass();
        $testObject->setValue('some other string');

        $testJson = $serializer->serialize($testObject,JsonLDEncoder::FORMAT);

        self::assertEquals($expectJson, $testJson);
    }

    public function testArraySerialize() : void
    {
        $serializer = SerializerHelper::create([]);
        $expectJson = '[{"@type":"SimpleClass","value":"some other string","id":"232d2c41-278a-4377-bd9d-c6046494ceaf"},{"@type":"SimpleClass","value":"different value","id":"232d2c41-278a-4377-bd9d-c6046494ceaf"}]';

        $obj1 = new SimpleClass();
        $obj1->setValue('some other string');

        $obj2 = new SimpleClass();
        $obj2->setValue('different value');

        $objects = [$obj1, $obj2];

        $testJson = $serializer->serialize($objects, JsonLDEncoder::FORMAT);

        self::assertEquals($testJson, $expectJson);
    }

    public function testDataArraySerializer() : void
    {
        $serializer = SerializerHelper::create([]);
        $expectJson = '{"@type":"DynamicArrayClass","array":{"MapArray":[{"label":"english","iso":"en"},{"label":"deutsche","iso":"de"}]},"id":"3e147484-01fd-4176-b8f4-43ef623fb092"}';

        $dynamicArrayObj = new DynamicArrayClass;
        $dynamicArrayObj->setArray([
            'MapArray' => (object)[
                [
                    'label' => 'english',
                    'iso' => 'en'
                ],
                [
                    'label' => 'deutsche',
                    'iso' => 'de'
                ]
            ]
        ]);

        $testJson = $serializer->serialize($dynamicArrayObj, JsonLDEncoder::FORMAT);

        self::assertEquals($expectJson, $testJson);
    }

    public function testNestedNormalizeNoTypeException() : void
    {
        $this->expectException(NotNormalizableValueException::class);
        $serializer = SerializerHelper::create([]);
        $testJson = <<<JSON
        {
            "@type": "NestedClass",
            "string": "some random string",
            "simpleClass": {
                "value": "some other string"
            }
        }
        JSON;

        $serializer->deserialize(
            $testJson,
            '',
            JsonLDEncoder::FORMAT,
            $this->getContextWithMapping(NestedClass::class)
        );
    }

    public function testNestedNormalizeMissingClassException() : void
    {
        $this->expectException(JsonLDSerializationException::class);
        $serializer = SerializerHelper::create([]);
        $testJson = <<<JSON
        {
            "@type": "NotFoundClass",
            "string": "some random string"
        }
        JSON;

        $serializer->deserialize($testJson, '', JsonLDEncoder::FORMAT);
    }

    public function testCircularSerialize() : void
    {
        $serializer = SerializerHelper::create([]);
        $expectJson = '{"@type":"CircularParentWithId","id":"232d2c41-278a-4377-bd9d-c6046494ceaf","children":[{"@type":"CircularChild","value":"some other string","parent":{"@type":"CircularParentWithId","@id":"232d2c41-278a-4377-bd9d-c6046494ceaf","id":"232d2c41-278a-4377-bd9d-c6046494ceaf"}},{"@type":"CircularChild","value":"different value","parent":{"@type":"CircularParentWithId","@id":"232d2c41-278a-4377-bd9d-c6046494ceaf","id":"232d2c41-278a-4377-bd9d-c6046494ceaf"}}]}';

        $obj1 = new CircularChild();
        $obj1->setValue('some other string');

        $obj2 = new CircularChild();
        $obj2->setValue('different value');

        $parent = new CircularParentWithId();
        $obj1->setParent($parent);
        $obj2->setParent($parent);

        $parent->setChildren([$obj1, $obj2]);

        $testJson = $serializer->serialize($parent, JsonLDEncoder::FORMAT);

        self::assertEquals($testJson, $expectJson);
    }

    public function testCircularSerialize_NoId() : void
    {
        $this->expectException(JsonLDException::class);
        $serializer = SerializerHelper::create([]);

        $obj1 = new CircularChild();
        $obj1->setValue('some other string');

        $obj2 = new CircularChild();
        $obj2->setValue('different value');

        $parent = new CircularParent();
        $obj1->setParent($parent);
        $obj2->setParent($parent);

        $parent->setChildren([$obj1, $obj2]);

        $serializer->serialize($parent, JsonLDEncoder::FORMAT);
    }

    private function getContextWithMapping(string $className) : array
    {
        return [
            JsonLDNormalizer::MAPPPING_KEY => new MappingEndpoint($className, 'http://localhost/blah')
        ];
    }
}
