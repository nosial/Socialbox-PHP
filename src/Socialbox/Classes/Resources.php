<?php

namespace Socialbox\Classes;

use InvalidArgumentException;
use Socialbox\Enums\DatabaseObjects;

class Resources
{
    public static function getDatabaseResource(DatabaseObjects $object): string
    {
        $tables_directory = __DIR__ . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'database';
        return $tables_directory . DIRECTORY_SEPARATOR . $object->value;
    }
}