<?php

namespace Socialbox\Classes\CliCommands;

use Exception;
use LogLib\Log;
use PDOException;
use Socialbox\Abstracts\CacheLayer;
use Socialbox\Classes\Configuration;
use Socialbox\Classes\Cryptography;
use Socialbox\Classes\Database;
use Socialbox\Classes\Resources;
use Socialbox\Enums\DatabaseObjects;
use Socialbox\Exceptions\DatabaseOperationException;
use Socialbox\Interfaces\CliCommandInterface;
use Socialbox\Managers\VariableManager;

class InitializeCommand implements CliCommandInterface
{
    /**
     * @inheritDoc
     */
    public static function execute(array $args): int
    {
        if(Configuration::getConfiguration()['instance']['enabled'] === false && !isset($args['force']))
        {
            Log::info('net.nosial.socialbox', 'Socialbox is disabled. Use --force to initialize the instance or set `instance.enabled` to True in the configuration');
            return 1;
        }

        Log::info('net.nosial.socialbox', 'Initializing Socialbox...');

        if(Configuration::getCacheConfiguration()->isEnabled())
        {
            Log::verbose('net.nosial.socialbox', 'Clearing cache layer...');
            CacheLayer::getInstance()->clear();
        }

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
            catch(Exception $e)
            {
                Log::error('net.nosial.socialbox', "Failed to initialize database object {$object->value}: {$e->getMessage()}", $e);
                return 1;
            }
        }

        try
        {

            if(!VariableManager::variableExists('PUBLIC_KEY') || !VariableManager::variableExists('PRIVATE_KEY'))
            {
                Log::info('net.nosial.socialbox', 'Generating new key pair...');

                $keyPair = Cryptography::generateKeyPair();
                VariableManager::setVariable('PUBLIC_KEY', $keyPair->getPublicKey());
                VariableManager::setVariable('PRIVATE_KEY', $keyPair->getPrivateKey());

                Log::info('net.nosial.socialbox', 'Set the DNS TXT record for the public key to the following value:');
                Log::info('net.nosial.socialbox', "socialbox-key={$keyPair->getPublicKey()}");
            }
        }
        catch(DatabaseOperationException $e)
        {
            Log::error('net.nosial.socialbox', "Failed to generate key pair: {$e->getMessage()}", $e);
            return 1;
        }

        Log::info('net.nosial.socialbox', 'Socialbox has been initialized successfully');
        return 0;
    }

    /**
     * @inheritDoc
     */
    public static function getHelpMessage(): string
    {
        return "Initialize Command - Initializes Socialbox for first-runs\n" .
               "Usage: socialbox init [arguments]\n\n" .
               "Arguments:\n" .
               "  --force - Forces the initialization process to run even the instance is disabled\n";
    }

    /**
     * @inheritDoc
     */
    public static function getShortHelpMessage(): string
    {
        return "Initializes Socialbox for first-runs";
    }
}