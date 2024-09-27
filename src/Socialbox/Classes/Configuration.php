<?php

namespace Socialbox\Classes;

class Configuration
{
    private static ?array $configuration = null;

    public static function getConfiguration(): array
    {
        if(self::$configuration === null)
        {
            $config = new \ConfigLib\Configuration('socialbox');

            // False by default, requires the user to enable it.
            $config->setDefault('instance.enabled', false);

            $config->setDefault('security.display_internal_exceptions', false);

            $config->setDefault('database.host', '127.0.0.1');
            $config->setDefault('database.port', 3306);
            $config->setDefault('database.username', 'root');
            $config->setDefault('database.password', 'root');
            $config->setDefault('database.name', 'test');
            $config->save();

            self::$configuration = $config->getConfiguration();
        }

        return self::$configuration;
    }
}