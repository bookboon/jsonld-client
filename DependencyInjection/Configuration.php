<?php

namespace Bookboon\JsonLDClient\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('jsonldclient');

        $treeBuilder
            ->getRootNode()
            ->children()
            ->scalarNode('default_class_namespace')->isRequired()->end()
            ->arrayNode('mappings')
                ->beforeNormalization()
                    ->ifArray()
                    ->then(static function ($v) {
                        $outArray = [];
                        foreach ($v as $type => $uri) {
                            $outArray[] = isset($uri['uri']) ? $uri : [
                                'type' => $type,
                                'uri' => $uri,
                                'renamed_properties' => [],
                            ];
                        }
                        return $outArray;
                    })
                ->end()
                ->isRequired()
                ->requiresAtLeastOneElement()
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('type')->end()
                        ->scalarNode('uri')->end()
                        ->arrayNode('renamed_properties')
                            ->useAttributeAsKey('name')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                            ->end()
                    ->end()
                ->end()
            ->end()
            ;

        return $treeBuilder;
    }
}
