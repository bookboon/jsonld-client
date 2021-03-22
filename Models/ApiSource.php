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
     * @param string|null $pointer
     * @return void
     */
    public function setPointer(?string $pointer) : void
    {
        $this->pointer = $pointer;
    }

    /**
     * @return string|null
     */
    public function getParameter() : ?string
    {
        return $this->parameter;
    }

    /**
     * @param string|null $parameter
     * @return void
     */
    public function setParameter(?string $parameter) : void
    {
        $this->parameter = $parameter;
    }
}
