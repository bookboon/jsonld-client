<?php

namespace Bookboon\JsonLDClient\Models;

/**
 * Class ApiError
 * @package Bookboon\JsonLDClient\Models
 *
 * See this for details https://jsonapi.org/format/#errors
 */
class ApiError
{
    protected $id;
    protected $status;
    protected $code;
    protected $title;
    protected $detail;
    protected $source;

    /**
     * @return string|null
     */
    public function getId() : ?string
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getStatus() : ?string
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function getCode() : ?string
    {
        return $this->code;
    }

    /**
     * @return string|null
     */
    public function getTitle() : ?string
    {
        return $this->title;
    }

    /**
     * @return string|null
     */
    public function getDetail() : ?string
    {
        return $this->detail;
    }

    /**
     * @return ApiSource|null
     */
    public function getSource() : ?ApiSource
    {
        return $this->source;
    }

    /**
     * @param string|null $id
     * @return void
     */
    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    /**
     * @param string|null $status
     * @return void
     */
    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    /**
     * @param string|null $code
     * @return void
     */
    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    /**
     * @param string|null $title
     * @return void
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * @param string|null $detail
     * @return void
     */
    public function setDetail(?string $detail): void
    {
        $this->detail = $detail;
    }

    /**
     * @param ApiSource|null $source
     * @return void
     */
    public function setSource(?ApiSource $source): void
    {
        $this->source = $source;
    }
}
