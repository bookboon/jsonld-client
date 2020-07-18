<?php

namespace Bookboon\JsonLDClient\Tests\Fixtures\Models;

class CircularParentWithId extends CircularParent
{
    public function getId() : string
    {
        return '232d2c41-278a-4377-bd9d-c6046494ceaf';
    }
}
