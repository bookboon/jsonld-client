<?php

namespace Bookboon\JsonLDClient\Tests\Fixtures\Models;

class ChildClass
{
    private string $title = '';

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

}