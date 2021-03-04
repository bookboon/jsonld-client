<?php

namespace Bookboon\JsonLDClient\Tests\Fixtures\Models;

use DateTime;

class DatedClass
{
    protected ?DateTime $created = null;

    /**
     * @return DateTime|null
     */
    public function getCreated() : ?DateTime
    {
        return $this->created;
    }

    /**
     * @param DateTime|null $created
     * @return void
     */
    public function setCreated(?DateTime $created): void
    {
        $this->created = $created;
    }
}
