<?php

namespace Bookboon\JsonLDClient\Tests\Fixtures;

use Bookboon\JsonLDClient\DependencyInjection\JsonLDClientExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Builder
{
    private $container;
    private $extention;

    public function __construct()
    {
        $this->container = $this->getContainer();
        $this->extention = $this->getExtension();
    }

    public function get(array $mapping, string $default_class_namespace) : ContainerBuilder
    {

        $this->extention->load(
            [
                'jsonldclient' => [
                    'mappings' => $mapping,
                    'default_class_namespace' => $default_class_namespace
                ]
            ],
            $this->container
        );

        return $this->container;
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
    protected function getContainer()
    {
        return new ContainerBuilder();
    }
}
