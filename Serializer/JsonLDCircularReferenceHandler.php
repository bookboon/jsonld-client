<?php

namespace Bookboon\JsonLDClient\Serializer;

use Bookboon\JsonLDClient\Client\JsonLDException;

class JsonLDCircularReferenceHandler
{
    public function __invoke($object)
    {
        if (false === method_exists($object, 'getId')) {
            throw new JsonLDException('Cannot serialize circular reference on non-id objects');
        }

        $id = $object->getId();
        /** @var string|false $shortClass */
        $shortClass = get_class($object);

        if (false === $shortClass) {
            throw new JsonLDException('Cannot get classname');
        }

        if (($lastPos = strrpos($shortClass, "\\")) !== false) {
            $shortClass = substr($shortClass, 1 + $lastPos);
        }

        return [
            '@type' => $shortClass,
            '@id' => $id,
            'id' => $id
        ];
    }
}
