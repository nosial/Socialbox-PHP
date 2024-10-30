<?php

namespace Socialbox\Classes;

use LogLib\Log;
use function Symfony\Component\String\s;

class Logger
{
    private static ?\LogLib\Logger $logger = null;

    /**
     * @return \LogLib\Logger
     */
    public static function getLogger(): \LogLib\Logger
    {
        if(self::$logger === null)
        {
            self::$logger = new \LogLib\Logger("net.nosial.socialbox");
            self::$logger->setConsoleLoggingEnabled(Configuration::getLoggingConfiguration()->isConsoleLoggingEnabled());
            self::$logger->setConsoleLoggingLevel(Configuration::getLoggingConfiguration()->getConsoleLoggingLevel());
            self::$logger->setFileLoggingEnabled(Configuration::getLoggingConfiguration()->isFileLoggingEnabled());
            self::$logger->setFileLoggingLevel(Configuration::getLoggingConfiguration()->getFileLoggingLevel());

            Log::registerExceptionHandler();
            Log::register(self::$logger);
        }

        return self::$logger;
    }
}