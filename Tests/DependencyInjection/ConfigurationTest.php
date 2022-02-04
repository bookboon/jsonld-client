<?php

namespace Bookboon\JsonLDClient\Tests\DependencyInjection;

use Bookboon\JsonLDClient\DependencyInjection\JsonLDClientExtension;
use Bookboon\JsonLDClient\Mapping\MappingCollection;
use Bookboon\JsonLDClient\Mapping\MappingEndpoint;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConfigurationTest extends TestCase
{
    /**
     * @var JsonLDClientExtension
     */
    private JsonLDClientExtension $extension;

    /**
     * Root name of the configuration
     *
     * @var string
     */
    private string $root;

    public function setUp() : void
    {
        parent::setUp();

        $this->extension = $this->getExtension();
        $this->root      = "jsonldclient";
    }

    public function testGetConfigWithDefaultValues() : void
    {
        $this->extension->load(
            [
                'jsonldclient' => [
                    'mappings' => [
                        'TestApp\\Entity\\Class' => 'https://test'
                    ]
                ]
            ],
            $container = $this->getContainer()
        );

        $expected = [
            new MappingEndpoint('TestApp\\Entity\\Class', 'https://test')
        ];

        self::assertTrue($container->has(MappingCollection::class));
        $mappingColl = $container->get(MappingCollection::class);
        self::assertNotNull($mappingColl);
        self::assertEquals($expected, $mappingColl->get());
    }

    public function testGetConfigWithFullClassName() : void
    {
        $this->extension->load(
            [
                'jsonldclient' => [
                    'mappings' => [
                        'OtherApp\\Entity\\Class' => 'https://test'
                    ]
                ]
            ],
            $container = $this->getContainer()
        );
        $expected = [
            new MappingEndpoint('OtherApp\\Entity\\Class', 'https://test')
        ];

        self::assertTrue($container->has(MappingCollection::class));

        $mappingColl = $container->get(MappingCollection::class);
        self::assertNotNull($mappingColl);
        self::assertEquals($expected, $mappingColl->get());
    }

    public function testGetConfigWithVerboseMapping() : void
    {
        $this->extension->load(
            [
                'jsonldclient' => [
                    'mappings' => [
                        [
                            'type' => 'TestApp\\Entity\\Class',
                            'uri' => 'https://test'
                        ]
                    ]
                ]
            ],
            $container = $this->getContainer()
        );
        $expected = [
            new MappingEndpoint('TestApp\\Entity\\Class', 'https://test')
        ];

        self::assertTrue($container->has(MappingCollection::class));
        $mappingColl = $container->get(MappingCollection::class);
        self::assertNotNull($mappingColl);
        self::assertEquals($expected, $mappingColl->get());
    }

    public function testGetConfigWithVerboseMappingInvalid() : void
    {
        $this->expectException(InvalidTypeException::class);
        $this->extension->load(
            [
                'jsonldclient' => [
                    'mappings' => [
                        [
                            'type' => 'TestApp\\Entity\\Class'
                        ]
                    ]
                ]
            ],
            $container = $this->getContainer()
        );
    }

    /**
     * @return JsonLDClientExtension
     */
    protected function getExtension() : JsonLDClientExtension
    {
        return new JsonLDClientExtension();
    }

    /**
     * @return ContainerBuilder
     */
    private function getContainer() : ContainerBuilder
    {
        return new ContainerBuilder();
    }
}
