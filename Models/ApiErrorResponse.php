<?php

namespace Bookboon\JsonLDClient\Models;

class ApiErrorResponse
{
    protected $errors = [];

    /**
     * @return ApiError[]
     */
    public function getErrors() : array
    {
        return $this->errors;
    }

    /**
     * @param ApiError[] $errors
     * @return void
     */
    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }

    public function __toString() {
        return implode(
            ',',
            array_map(
                static function ($item) {
                    $message = $item->getTitle() ?? $item->getDetail();
                    return "{$item->getStatus()}: {$message}";
                },
                $this->getErrors()
            )
        );
    }
}
