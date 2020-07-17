<?php

namespace Bookboon\ApiBundle;

use Bookboon\ApiBundle\DependencyInjection\BookboonApiExtension;
use Bookboon\JsonLDClient\DependencyInjection\JsonLDClientExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class JsonLDClient extends Bundle
{
    public function getContainerExtension()
    {
        return new JsonLDClientExtension();
    }
}
