<?php

namespace Bookboon\JsonLDClient\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class JsonLDEntity
{
    private string $url;
    private bool $singleton;

    public function __construct(string $url, bool $singleton = false)
    {
        $this->url = $url;
        $this->singleton = $singleton;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return bool
     */
    public function isSingleton(): bool
    {
        return $this->singleton;
    }

    /**
     * @param bool $singleton
     */
    public function setSingleton(bool $singleton): void
    {
        $this->singleton = $singleton;
    }
}
