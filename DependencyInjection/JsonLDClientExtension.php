<?php

namespace Bookboon\JsonLDClient\DependencyInjection;

use Bookboon\ApiBundle\Configuration\ApiConfiguration;
use Bookboon\ApiBundle\Helper\ConfigurationHolder;
use Bookboon\JsonLDClient\Mapping\MappingCollection;
use Symfony\Component\Config\FileLocator;
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
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->register(MappingCollection::class, MappingCollection::class)
            ->setFactory(array(MappingCollection::class, 'create'))
            ->addArgument($config['mappings'])
            ->addArgument($config['default_class_namespace'])
            ->setPublic(false);

        $container->setParameter(
            "{$this->getAlias()}.default_class_namespace",
            $config['default_class_namespace']
        );

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yml');
    }

    public function getAlias()
    {
        return 'jsonldclient';
    }

    public function getXsdValidationBasePath()
    {
        return 'http://bookboon.com/schema/dic/' . $this->getAlias();
    }
}
