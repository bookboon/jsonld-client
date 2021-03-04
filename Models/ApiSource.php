<?php

namespace Bookboon\JsonLDClient\Models;

class ApiSource
{
    protected ?string $pointer = null;
    protected ?string $parameter = null;

    /**
     * @return string|null
     */
    public function getPointer() : ?string
    {
        return $this->pointer;
    }

    /**
     * @return string|null
     */
    public function getParameter() : ?string
    {
        return $this->parameter;
    }


}
