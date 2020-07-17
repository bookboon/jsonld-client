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
    private $extension;

    /**
     * Root name of the configuration
     *
     * @var string
     */
    private $root;

    public function setUp() : void
    {
        parent::setUp();

        $this->extension = $this->getExtension();
        $this->root      = "jsonldclient";
    }

    public function testGetConfigWithDefaultValues()
    {
        $this->extension->load(
            [
                'jsonldclient' => [
                    'mappings' => [
                        'Class' => 'https://test'
                    ],
                    'default_class_namespace' => 'TestApp\\Entity'
                ]
            ],
            $container = $this->getContainer()
        );

        $this->assertTrue($container->hasParameter($this->root . ".default_class_namespace"));
        $this->assertEquals("TestApp\\Entity", $container->getParameter($this->root . ".default_class_namespace"));

        $expected = [
            new MappingEndpoint('TestApp\\Entity\\Class', 'https://test')
        ];

        $this->assertTrue($container->has(MappingCollection::class));
        $this->assertEquals($expected, $container->get(MappingCollection::class)->get());
    }

    public function testGetConfigWithFullClassName()
    {
        $this->extension->load(
            [
                'jsonldclient' => [
                    'mappings' => [
                        'OtherApp\\Entity\\Class' => 'https://test'
                    ],
                    'default_class_namespace' => 'TestApp\\Entity'
                ]
            ],
            $container = $this->getContainer()
        );
        $expected = [
            new MappingEndpoint('OtherApp\\Entity\\Class', 'https://test')
        ];

        $this->assertTrue($container->has(MappingCollection::class));
        $this->assertEquals($expected, $container->get(MappingCollection::class)->get());
    }

    public function testGetConfigWithVerboseMapping()
    {
        $this->extension->load(
            [
                'jsonldclient' => [
                    'mappings' => [
                        [
                            'type' => 'Class',
                            'uri' => 'https://test'
                        ]
                    ],
                    'default_class_namespace' => 'TestApp\\Entity'
                ]
            ],
            $container = $this->getContainer()
        );
        $expected = [
            new MappingEndpoint('TestApp\\Entity\\Class', 'https://test')
        ];

        $this->assertTrue($container->has(MappingCollection::class));
        $this->assertEquals($expected, $container->get(MappingCollection::class)->get());
    }

    public function testGetConfigWithVerboseMappingInvalid()
    {
        $this->expectException(InvalidTypeException::class);
        $this->extension->load(
            [
                'jsonldclient' => [
                    'mappings' => [
                        [
                            'type' => 'Class'
                        ]
                    ],
                    'default_class_namespace' => 'TestApp\\Entity'
                ]
            ],
            $container = $this->getContainer()
        );
    }

    /**
     * @return JsonLDClientExtension
     */
    protected function getExtension()
    {
        return new JsonLDClientExtension();
    }

    /**
     * @return ContainerBuilder
     */
    private function getContainer()
    {
        return new ContainerBuilder();
    }
}
