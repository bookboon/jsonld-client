<?php

namespace Bookboon\JsonLDClient\Tests\Serializer;

use Bookboon\JsonLDClient\Mapping\MappingEndpoint;
use Bookboon\JsonLDClient\Serializer\JsonLDEncoder;
use Bookboon\JsonLDClient\Tests\Fixtures\Models\ClassWithStdClassProperty;
use Bookboon\JsonLDClient\Tests\Fixtures\SerializerHelper;
use PHPUnit\Framework\TestCase;

class StdClassNormalizerTest extends TestCase
{
    public function testSimpleDeserialize() : void
    {
        $holderClass = new ClassWithStdClassProperty();
        $providerOptions = new \stdClass;
        $providerOptions->firstLevelVar = new \stdClass();
        $providerOptions->firstLevelVar->secondLevelVar = new \stdClass();
        $holderClass->setStdClassVar($providerOptions);

        $serializer = SerializerHelper::create([
            new MappingEndpoint(ClassWithStdClassProperty::class, '/api/v1/dummies', [
                'value' => '@ClassWithStdClassProperty'
            ])
        ]);

        $serializedJson = $serializer->serialize($holderClass, JsonLDEncoder::FORMAT);

        $testJson = '{"@type":"ClassWithStdClassProperty","stdClassVar":{"firstLevelVar":{"secondLevelVar":{}}}}';

        $this->assertEquals($testJson, $serializedJson);
    }
}
