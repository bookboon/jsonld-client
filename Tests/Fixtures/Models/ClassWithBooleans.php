<?php


namespace Bookboon\JsonLDClient\Tests\Fixtures\Models;


class ClassWithBooleans
{
    private bool $isEnabled = false;

    private ?bool $isExample = false;

    public function getId() : string
    {
        return '397d2ea5-e7ac-4c72-9900-f70d34de9b9c';
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    /**
     * @param bool $isEnabled
     */
    public function setIsEnabled(bool $isEnabled): void
    {
        $this->isEnabled = $isEnabled;
    }

    public function isExample(): ?bool
    {
        return $this->isExample;
    }

    public function setIsExample(?bool $isExample): void
    {
        $this->isExample = $isExample;
    }
}
