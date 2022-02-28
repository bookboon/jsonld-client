<?php

namespace Bookboon\JsonLDClient\Tests\Fixtures\OtherModels;

class ChildClass
{
    private ?string $title = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }
    
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

}