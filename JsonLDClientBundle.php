<?php

namespace Bookboon\JsonLDClient;

use Bookboon\JsonLDClient\DependencyInjection\JsonLDClientExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class JsonLDClientBundle extends Bundle
{
    public function getContainerExtension() : ?ExtensionInterface
    {
        return new JsonLDClientExtension();
    }
}
