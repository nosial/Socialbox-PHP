<?php

namespace Socialbox\Classes\CliCommands;

use LogLib\Log;
use PDOException;
use Socialbox\Classes\Configuration;
use Socialbox\Classes\Database;
use Socialbox\Classes\Resources;
use Socialbox\Enums\DatabaseObjects;
use Socialbox\Interfaces\CliCommandInterface;

class InitializeCommand implements CliCommandInterface
{
    /**
     * Executes the command with the given arguments.
     *
     * @param array $args An array of arguments to be processed.
     * @return int The result of the execution as an integer.
     */
    public static function execute(array $args): int
    {
        if(Configuration::getConfiguration()['instance']['enabled'] === false && !isset($args['force']))
        {
            Log::info('net.nosial.socialbox', 'Socialbox is disabled. Use --force to initialize the instance or set `instance.enabled` to True in the configuration');
            return 1;
        }

        print("Initializing Socialbox...\n");
        foreach(DatabaseObjects::casesOrdered() as $object)
        {
            Log::verbose('net.nosial.socialbox', "Initializing database object {$object->value}");

            try
            {
                Database::getConnection()->exec(file_get_contents(Resources::getDatabaseResource($object)));
            }
            catch (PDOException $e)
            {
                // Check if the error code is for "table already exists"
                if ($e->getCode() === '42S01')
                {
                    Log::warning('net.nosial.socialbox', "Database object {$object->value} already exists, skipping...");
                    continue;
                }
                else
                {
                    Log::error('net.nosial.socialbox', "Failed to initialize database object {$object->value}: {$e->getMessage()}", $e);
                    return 1;
                }
            }
            catch(\Exception $e)
            {
                Log::error('net.nosial.socialbox', "Failed to initialize database object {$object->value}: {$e->getMessage()}", $e);
                return 1;
            }
        }

        Log::info('net.nosial.socialbox', 'Socialbox has been initialized successfully');
        return 0;
    }

    /**
     * Returns the help message for the command.
     *
     * @return string The help message.
     */
    public static function getHelpMessage(): string
    {
        return "Initialize Command - Initializes Socialbox for first-runs\n" .
               "Usage: socialbox init [arguments]\n\n" .
               "Arguments:\n" .
               "  --force - Forces the initialization process to run even the instance is disabled\n";
    }

    /**
     * Returns a short help message for the command.
     *
     * @return string
     */
    public static function getShortHelpMessage(): string
    {
        return "Initializes Socialbox for first-runs";
    }
}