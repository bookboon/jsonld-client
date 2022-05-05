<?php

namespace Bookboon\JsonLDClient\Tests\Fixtures;

use Psr\SimpleCache\CacheInterface;

class MemoryCache implements CacheInterface
{
    public array $values = [];

    public function get($key, $default = null)
    {
        return $this->values[$key] ?? $default;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $this->values[$key] = $value;
        return true;
    }

    public function delete($key): bool
    {
        unset($this->values[$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->values = [];
        return true;
    }

    public function getMultiple($keys, $default = null): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->values[$key] ?? $default;
        }

        return $result;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->values[$key] = $value;
        }

        return true;
    }

    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $key) {
            unset($this->values[$key]);
        }

        return true;
    }

    public function has($key): bool
    {
        return isset($this->values[$key]);
    }
}
