<?php

namespace Bookboon\JsonLDClient\Models;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

class ConstantBufferStream implements StreamInterface
{
    private int $pos = 0;
    public function __construct(private string $buffer)
    {
    }

    public function __toString(): string
    {
        return substr($this->buffer, $this->pos);
    }

    public function close(): void
    {
        // do nothing
    }

    public function detach()
    {
        // do nothing
        return null;
    }

    public function getSize(): int
    {
        return strlen($this->buffer) - $this->pos;
    }

    public function tell(): int
    {
        return $this->pos;
    }

    public function eof(): bool
    {
        return $this->pos === strlen($this->buffer);
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        switch ($whence) {
            case SEEK_SET:
                $this->pos = $offset;
                break;
            case SEEK_CUR:
                $this->pos += $offset;
                break;
            case SEEK_END:
                $this->pos = strlen($this->buffer) + $offset;
                break;
        }

        $this->pos = max($this->pos, 0);
        $this->pos = min($this->pos, strlen($this->buffer));
    }

    public function rewind(): void
    {
        $this->pos = 0;
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write($string): int
    {
        throw new RuntimeException("Cannot write to ConstantBufferStream");
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read($length): string
    {
        return substr($this->buffer, $this->pos, $length);
    }

    public function getContents(): string
    {
        return (string)$this;
    }

    public function getMetadata($key = null)
    {
        return null;
    }
}
