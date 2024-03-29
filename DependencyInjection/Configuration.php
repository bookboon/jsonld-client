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
            ->scalarNode('default_class_namespace')->setDeprecated("bookboon/jsonld-client","v2.0")->end()
            ->arrayNode('apis')
                ->beforeNormalization()
                    ->ifArray()
                    ->then(static function ($v) {
                        $outArray = [];
                        foreach ($v as $namespace => $uri) {
                            $outArray[] = isset($uri['uri']) ? $uri : [
                                'namespace' => $namespace,
                                'uri' => $uri
                            ];
                        }
                        return $outArray;
                    })
                ->end()
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('namespace')->end()
                        ->scalarNode('uri')->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('mappings')->setDeprecated("bookboon/jsonld-client","v2.0")
                ->beforeNormalization()
                    ->ifArray()
                    ->then(static function ($v) {
                        $outArray = [];
                        foreach ($v as $type => $uri) {
                            $outArray[] = isset($uri['uri']) ? $uri : [
                                'type' => $type,
                                'uri' => $uri,
                                'renamed_properties' => [],
                                'singleton' => false
                            ];
                        }
                        return $outArray;
                    })
                ->end()
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('type')->end()
                        ->scalarNode('uri')->end()
                        ->arrayNode('renamed_properties')
                            ->useAttributeAsKey('name')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end()
                        ->booleanNode('singleton')->defaultFalse()->end()
                    ->end()
                ->end()
            ->end()
            ;

        return $treeBuilder;
    }
}
