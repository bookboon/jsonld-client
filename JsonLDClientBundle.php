<?php

namespace Bookboon\JsonLDClient;

use Bookboon\JsonLDClient\DependencyInjection\JsonLDClientExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class JsonLDClientBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new JsonLDClientExtension();
    }
}
