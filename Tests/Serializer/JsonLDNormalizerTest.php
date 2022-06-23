<?php

namespace Bookboon\JsonLDClient\Tests\Serializer;

use ArrayObject;
use Bookboon\JsonLDClient\Client\JsonLDException;
use Bookboon\JsonLDClient\Client\JsonLDSerializationException;
use Bookboon\JsonLDClient\Mapping\MappingApi;
use Bookboon\JsonLDClient\Mapping\MappingEndpoint;
use Bookboon\JsonLDClient\Serializer\JsonLDEncoder;
use Bookboon\JsonLDClient\Serializer\JsonLDNormalizer;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\ChildClass;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\CircularChild;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\CircularParent;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\CircularParentWithId;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\ClassWithMapProperty;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\DatedClass;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\DummyClass;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\DynamicArrayClass;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\NestedArrayClass;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\NestedClass;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\NestedClassWithoutDoc;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\SimpleClass;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\SimpleClassNoMangling;
use Bookboon\JsonLDClient\Tests\Fixtures\SerializerHelper;
use DateTime;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\SerializerInterface;

class JsonLDNormalizerTest extends TestCase
{
    public function testSimpleDeserialize(): void
    {
        $serializer = SerializerHelper::create([], [], [
            new MappingApi('Bookboon\JsonLDClient\Tests\Fixtures\Models', 'http://example.com/api/v1')
        ]);
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
            ]),
        ], [], [
            new MappingApi('Bookboon\JsonLDClient\Tests\Fixtures\Models', 'http://example.com')
        ]);

        $testJson = <<<JSON
        {
            "@type": "SimpleClass",
            "@MangledValue": "some random string"
        }
        JSON;

        $object = $serializer->deserialize($testJson, '', JsonLDEncoder::FORMAT, [JsonLDNormalizer::NAMESPACE_KEY => 'Bookboon\JsonLDClient\Tests\Fixtures\Models']);

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

    public function testDatedDeserialize_HasNullDate(): void
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

    public function testNestedDeserialize(): void
    {
        $serializer = SerializerHelper::create([], [], [
            new MappingApi('Bookboon\JsonLDClient\Tests\Fixtures\Models', 'http://example.com/api/v1')
        ]);
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

    public function testNestedWithoutDocDeserialize(): void
    {
        $serializer = SerializerHelper::create([], [], [
            new MappingApi('Bookboon\JsonLDClient\Tests\Fixtures\Models', 'http://example.com/api/v1')
        ]);
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

    public function testNestedArrayDeserialize(): void
    {
        $serializer = SerializerHelper::create([], [], [
            new MappingApi('Bookboon\JsonLDClient\Tests\Fixtures\Models', 'http://example.com')
        ]);
        $testJson = <<<JSON
        {
            "@type": "NestedArrayClass",
            "string": "some random string",
            "simpleClasses": [
                {
                    "@type": "SimpleClass",
                    "@MangledValue": "some other string"
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

    public function testNestedArraySerialize(): void
    {
        $serializer = SerializerHelper::create([], [], [
            new MappingApi('Bookboon\JsonLDClient\Tests\Fixtures\Models', 'http://example.com')
        ]);
        $obj = new NestedArrayClass();
        $simp1 = new SimpleClass();
        $id1 = $simp1->getId();
        $simp1->setValue("some other string");
        $simp2 = new SimpleClass();
        $id2 = $simp2->getId();
        $simp2->setValue("different value");
        $obj->setSimpleClasses([$simp1, $simp2]);
        $obj->setString("some random string");

        $expectedJson = <<<JSON
        {
            "@type": "NestedArrayClass",
            "string": "some random string",
            "simpleClasses": [
                {
                    "@type": "SimpleClass",
                    "id": "$id1",
                    "@MangledValue": "some other string"
                },
                {
                    "@type": "SimpleClass",
                    "id": "$id2",
                    "@MangledValue": "different value"
                }
            ]
        }
        JSON;

        $json = $serializer->serialize($obj, JsonLDEncoder::FORMAT);
        $this->assertJsonStringEqualsJsonString($expectedJson, $json);
    }

    public function testEmptyNestedArrayDeserialize(): void
    {
        $serializer = SerializerHelper::create([], [], [
            new MappingApi('Bookboon\JsonLDClient\Tests\Fixtures\Models', 'https://example.com/api/v1')
        ]);
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

    public function testArrayDeserialize(): void
    {
        $serializer = $this->getSerializerHelper();
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

    public function testSimpleSerialize(): void
    {
        $serializer = $this->getSerializerHelper();
        $expectJson = '{"@type":"SimpleClassNoMangling","value":"some other string","id":"232d2c41-278a-4377-bd9d-c6046494ceaf"}';
        $testObject = new SimpleClassNoMangling();
        $testObject->setValue('some other string');

        $testJson = $serializer->serialize($testObject, JsonLDEncoder::FORMAT);

        self::assertEquals($expectJson, $testJson);
    }

    public function testSerializeWithMapping(): void
    {
        $serializer = SerializerHelper::create(
            [
                new MappingEndpoint(
                    'Bookboon\JsonLDClient\Tests\Fixtures\Models\SimpleClassNoMangling',
                    '',
                    [
                        'value' => '@MangledValue'
                    ]
                )
            ],
            [],
            [
                new MappingApi('Bookboon\JsonLDClient\Tests\Fixtures\Models', 'https://example.com/api/v1')
            ]
        );

        $expectJson = '{"@type":"SimpleClassNoMangling","id":"232d2c41-278a-4377-bd9d-c6046494ceaf","@MangledValue":"some other string"}';
        $testObject = new SimpleClassNoMangling();
        $testObject->setValue('some other string');

        $testJson = $serializer->serialize($testObject, JsonLDEncoder::FORMAT);

        self::assertEquals($expectJson, $testJson);
    }

    public function testSerializeWithoutMapping(): void
    {
        $serializer = $this->getSerializerHelper();
        $expectJson = '{"@type":"SimpleClass","id":"232d2c41-278a-4377-bd9d-c6046494ceaf","@MangledValue":"some other string"}';
        $testObject = new SimpleClass();
        $testObject->setValue('some other string');

        $testJson = $serializer->serialize($testObject, JsonLDEncoder::FORMAT);

        self::assertEquals($expectJson, $testJson);
    }

    public function testArraySerialize(): void
    {
        $serializer = $this->getSerializerHelper();
        $expectJson = '[{"@type":"SimpleClassNoMangling","value":"some other string","id":"232d2c41-278a-4377-bd9d-c6046494ceaf"},{"@type":"SimpleClassNoMangling","value":"different value","id":"232d2c41-278a-4377-bd9d-c6046494ceaf"}]';

        $obj1 = new SimpleClassNoMangling();
        $obj1->setValue('some other string');

        $obj2 = new SimpleClassNoMangling();
        $obj2->setValue('different value');

        $objects = [$obj1, $obj2];

        $testJson = $serializer->serialize($objects, JsonLDEncoder::FORMAT);

        self::assertEquals($testJson, $expectJson);
    }

    public function testDataArraySerializer(): void
    {
        $serializer = $this->getSerializerHelper();
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

    public function testNestedNormalizeNoTypeException(): void
    {
        $this->expectException(NotNormalizableValueException::class);
        $serializer = $this->getSerializerHelper();
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

    public function testNestedNormalizeMissingClassException(): void
    {
        $this->expectException(JsonLDSerializationException::class);
        $serializer = $this->getSerializerHelper();
        $testJson = <<<JSON
        {
            "@type": "NotFoundClass",
            "string": "some random string"
        }
        JSON;

        $serializer->deserialize($testJson, '', JsonLDEncoder::FORMAT);
    }

    public function testCircularSerialize(): void
    {
        $serializer = $this->getSerializerHelper();
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

    public function testCircularSerialize_NoId(): void
    {
        $this->expectException(JsonLDException::class);
        $serializer = $this->getSerializerHelper();

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

    public function testClassWithObjectProperty(): void
    {
        $serializer = $this->getSerializerHelper();

        $testJson = '{"@type":"ClassWithMapProperty","objectProperty":{"hello":"helloworld"}}';

        /** @var ClassWithMapProperty $classWithObjectProperty */
        $classWithObjectProperty = $serializer->deserialize(
            $testJson,
            '',
            JsonLDEncoder::FORMAT,
            $this->getContextWithMapping(ClassWithMapProperty::class)
        );

        $objProperty = $classWithObjectProperty->getObjectProperty();
        $this->assertNotNull($objProperty);

        $this->assertInstanceOf(ArrayObject::class, $objProperty);
        $this->assertEquals('helloworld', $objProperty["hello"]);

        $serializedJson = $serializer->serialize($classWithObjectProperty, JsonLDEncoder::FORMAT);

        $this->assertJsonStringEqualsJsonString($testJson, $serializedJson);
    }

    public function testPrefersClassInOwnNamespaceRatherThanMapping(): void
    {
        $dummyClass = new DummyClass;
        $dummyClass->setName("dummyclass");
        $childClass = new ChildClass;
        $childClass->setTitle("childclass");

        $dummyClass->setChildClass($childClass);

        $serializer = SerializerHelper::create([
            new MappingEndpoint(DummyClass::class, '/api/v1/dummies', [
                'value' => '@DummyClass'
            ]),
            new MappingEndpoint(DummyClass::class, '/api/v1/childclasses', [
                'value' => '@ChildClass'
            ])
        ]);
        $serializedJson = $serializer->serialize($dummyClass, JsonLDEncoder::FORMAT);

        /** @var DummyClass $dummyClass */
        $dummyClass = $serializer->deserialize(
            $serializedJson,
            '',
            JsonLDEncoder::FORMAT,
            $this->getContextWithMapping(ClassWithMapProperty::class)
        );

        $childClass = $dummyClass->getChildClass();
        $this->assertNotNull($childClass);
        $this->assertEquals('childclass', $childClass->getTitle());
    }

    private function getContextWithMapping(string $className): array
    {
        return [
            JsonLDNormalizer::MAPPPING_KEY => new MappingEndpoint($className, 'http://localhost/blah')
        ];
    }

    private function getSerializerHelper() : SerializerInterface
    {
        return SerializerHelper::create(
            [],
            [],
            [
                new MappingApi(
                    'Bookboon\JsonLDClient\Tests\Fixtures\Models',
                    'https://example.com/api/v1'
                )
            ]
        );
    }
}
