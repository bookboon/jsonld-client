<?php

namespace Bookboon\JsonLDClient\DependencyInjection;

use Bookboon\ApiBundle\Configuration\ApiConfiguration;
use Bookboon\ApiBundle\Helper\ConfigurationHolder;
use Bookboon\JsonLDClient\Mapping\MappingCollection;
use Bookboon\JsonLDClient\Serializer\JsonLDMapNormalizer;
use Bookboon\JsonLDClient\Serializer\JsonLDNormalizer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class JsonLDClientExtension extends Extension
{
    /**
     * Loads a specific configuration.
     *
     * @param array $configs An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if (null !== $preConfig = $this->getConfiguration($configs, $container)) {
            $config = $this->processConfiguration($preConfig, $configs);
        } else {
            throw new \RuntimeException('Bad stuff happened');
        }

        $container->register(MappingCollection::class, MappingCollection::class)
            ->setFactory(array(MappingCollection::class, 'create'))
            ->addArgument($config['mappings'])
            ->addArgument($config['apis'] ?? '')
            ->setPublic(false);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yml');
    }

    public function getAlias() : string
    {
        return 'jsonldclient';
    }

    public function getXsdValidationBasePath()
    {
        return 'http://bookboon.com/schema/dic/' . $this->getAlias();
    }
}
