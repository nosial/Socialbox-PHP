<?php

namespace Socialbox\Classes;

use Socialbox\Classes\Configuration\CacheConfiguration;
use Socialbox\Classes\Configuration\DatabaseConfiguration;
use Socialbox\Classes\Configuration\RegistrationConfiguration;

class Configuration
{
    private static ?array $configuration = null;
    private static ?DatabaseConfiguration $databaseConfiguration = null;
    private static ?CacheConfiguration $cacheConfiguration = null;
    private static ?RegistrationConfiguration $registrationConfiguration = null;

    /**
     * Initializes the configuration settings for the application. This includes
     * settings for the instance, security, database, cache layer, and registration.
     *
     * @return void
     */
    private static function initializeConfiguration(): void
    {
        $config = new \ConfigLib\Configuration('socialbox');

        // False by default, requires the user to enable it.
        $config->setDefault('instance.enabled', false);

        $config->setDefault('security.display_internal_exceptions', false);

        // Database configuration
        $config->setDefault('database.host', '127.0.0.1');
        $config->setDefault('database.port', 3306);
        $config->setDefault('database.username', 'root');
        $config->setDefault('database.password', 'root');
        $config->setDefault('database.name', 'test');

        // Cache layer configuration
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

        // Registration configuration
        $config->setDefault('registration.enabled', true);
        $config->setDefault('registration.password_required', true);
        $config->setDefault('registration.otp_required', false);
        $config->setDefault('registration.display_name_required', false);
        $config->setDefault('registration.email_verification_required', false);
        $config->setDefault('registration.sms_verification_required', false);
        $config->setDefault('registration.phone_call_verification_required', false);
        $config->setDefault('registration.image_captcha_verification_required', true);

        $config->save();

        self::$configuration = $config->getConfiguration();
        self::$databaseConfiguration = new DatabaseConfiguration(self::$configuration['database']);
        self::$cacheConfiguration = new CacheConfiguration(self::$configuration['cache']);
        self::$registrationConfiguration = new RegistrationConfiguration(self::$configuration['registration']);
    }

    /**
     * Retrieves the current configuration array. If the configuration is not initialized,
     * it triggers the initialization process.
     *
     * @return array The current configuration array.
     */
    public static function getConfiguration(): array
    {
        if(self::$configuration === null)
        {
            self::initializeConfiguration();
        }

        return self::$configuration;
    }

    /**
     * Retrieves the current database configuration.
     *
     * @return DatabaseConfiguration The configuration settings for the database.
     */
    public static function getDatabaseConfiguration(): DatabaseConfiguration
    {
        if(self::$databaseConfiguration === null)
        {
            self::initializeConfiguration();
        }

        return self::$databaseConfiguration;
    }

    /**
     * Retrieves the current cache configuration. If the cache configuration
     * has not been initialized, it will initialize it first.
     *
     * @return CacheConfiguration The current cache configuration instance.
     */
    public static function getCacheConfiguration(): CacheConfiguration
    {
        if(self::$cacheConfiguration === null)
        {
            self::initializeConfiguration();
        }

        return self::$cacheConfiguration;
    }

    /**
     * Retrieves the registration configuration.
     *
     * This method returns the current RegistrationConfiguration instance.
     * If the configuration has not been initialized yet, it initializes it first.
     *
     * @return RegistrationConfiguration The registration configuration instance.
     */
    public static function getRegistrationConfiguration(): RegistrationConfiguration
    {
        if(self::$registrationConfiguration === null)
        {
            self::initializeConfiguration();
        }

        return self::$registrationConfiguration;
    }
}