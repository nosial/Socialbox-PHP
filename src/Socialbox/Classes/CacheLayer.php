<?php

    namespace Socialbox\Classes;
    
    use Exception;
    use Redis;

    class CacheLayer
    {
        private static ?Redis $instance = null;

        /**
         * Get the Redis instance, returns null if caching is turned off or there was an error connecting
         * to the Redis server.
         *
         * @return Redis|null
         */
        public static function getInstance(): ?Redis
        {
            // Return null if caching is turned off
            if(!Configuration::getCacheConfiguration()->isEnabled())
            {
                return null;
            }

            if (self::$instance === null)
            {
                try
                {
                    self::$instance = new Redis();
                    self::$instance->connect(
                        Configuration::getCacheConfiguration()->getHost(),
                        Configuration::getCacheConfiguration()->getPort()
                    );

                    if(Configuration::getCacheConfiguration()->getPassword() !== null)
                    {
                        self::$instance->auth(Configuration::getCacheConfiguration()->getPassword());
                    }

                    self::$instance->select(Configuration::getCacheConfiguration()->getDatabase());
                }
                catch (Exception $e)
                {
                    self::$instance = null;
                    Logger::getLogger()->critical($e->getMessage(), $e);
                    return null;
                }
            }

            return self::$instance;
        }
    }