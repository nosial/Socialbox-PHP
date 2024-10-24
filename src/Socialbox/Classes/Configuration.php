<?php

namespace Socialbox\Classes;

use Socialbox\Classes\Configuration\CacheConfiguration;
use Socialbox\Classes\Configuration\DatabaseConfiguration;

class Configuration
{
    private static ?array $configuration = null;
    private static ?DatabaseConfiguration $databaseConfiguration = null;
    private static ?CacheConfiguration $cacheConfiguration = null;

    private static function initializeConfiguration(): void
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
        $config->setDefault('cache.sessions.enabled', true);
        $config->setDefault('cache.sessions.ttl', 3600);
        $config->setDefault('cache.sessions.max', 1000);

        $config->save();

        self::$configuration = $config->getConfiguration();
        self::$databaseConfiguration = self::$configuration['database'];
        self::$cacheConfiguration = self::$configuration['cache'];
    }

    public static function getConfiguration(): array
    {
        if(self::$configuration === null)
        {
            self::initializeConfiguration();
        }

        return self::$configuration;
    }

    public static function getDatabaseConfiguration(): DatabaseConfiguration
    {
        if(self::$databaseConfiguration === null)
        {
            self::initializeConfiguration();
        }

        return self::$databaseConfiguration;
    }

    public static function getCacheConfiguration(): CacheConfiguration
    {
        if(self::$cacheConfiguration === null)
        {
            self::initializeConfiguration();
        }

        return self::$cacheConfiguration;
    }
}