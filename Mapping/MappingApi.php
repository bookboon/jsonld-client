<?php

namespace Bookboon\JsonLDClient\Mapping;

class MappingApi
{
    protected string $namespace;
    protected string $uri;

    public function __construct($namespace, $uri)
    {
        $this->namespace = $namespace;
        $this->uri = $uri;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }


}