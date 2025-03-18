<?php

    namespace Socialbox\Classes;


    class Logger
    {
        private static ?\LogLib2\Logger $logger = null;

        /**
         * @return \LogLib2\Logger
         */
        public static function getLogger(): \LogLib2\Logger
        {
            if(self::$logger === null)
            {
                self::$logger = new \LogLib2\Logger(Configuration::getInstanceConfiguration()->getName());

                // Don't register handlers if we are testing. This conflicts with PHPUnit.
                if(!defined('SB_TEST'))
                {
                    \LogLib2\Logger::registerHandlers();
                }
            }

            return self::$logger;
        }
    }