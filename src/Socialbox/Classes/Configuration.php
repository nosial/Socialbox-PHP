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

            $config->setDefault('cache.enabled', false);
            $config->setDefault('cache.engine', 'redis');
            $config->setDefault('cache.host', '127.0.0.1');
            $config->setDefault('cache.port', 6379);
            $config->setDefault('cache.username', null);
            $config->setDefault('cache.password', null);
            $config->setDefault('cache.database', 0);
            $config->setDefault('cache.variables.enabled', true);
            $config->setDefault('cache.variables.ttl', 3600);
            $config->setDefault('cache.variables.max', 1000);

            $config->save();

            self::$configuration = $config->getConfiguration();
        }

        return self::$configuration;
    }
}