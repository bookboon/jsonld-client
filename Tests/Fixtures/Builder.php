<?php

namespace Bookboon\JsonLDClient\Tests\Fixtures;

use Bookboon\JsonLDClient\DependencyInjection\JsonLDClientExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Builder
{
    private ContainerBuilder $container;
    private JsonLDClientExtension $extention;

    public function __construct()
    {
        $this->container = $this->getContainer();
        $this->extention = $this->getExtension();
    }

    public function get(array $mapping) : ContainerBuilder
    {

        $this->extention->load(
            [
                'jsonldclient' => [
                    'mappings' => $mapping
                ]
            ],
            $this->container
        );

        return $this->container;
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
    protected function getContainer() : ContainerBuilder
    {
        return new ContainerBuilder();
    }
}
