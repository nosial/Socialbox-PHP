<?php

namespace Socialbox\Interfaces;

use Socialbox\Abstracts\Entity;

interface SerializableInterface
{
    /**
     * Serializes the object to an array.
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Constructs the object from an array of data.
     *
     * @param array $data The data to construct the object from.
     */
    public static function fromArray(array $data): Entity;
}