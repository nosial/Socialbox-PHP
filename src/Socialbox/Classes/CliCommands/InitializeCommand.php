<?php

namespace Socialbox\Classes\CliCommands;

use Exception;
use LogLib\Log;
use PDOException;
use Socialbox\Abstracts\CacheLayer;
use Socialbox\Classes\Configuration;
use Socialbox\Classes\Cryptography;
use Socialbox\Classes\Database;
use Socialbox\Classes\Logger;
use Socialbox\Classes\Resources;
use Socialbox\Enums\DatabaseObjects;
use Socialbox\Exceptions\CryptographyException;
use Socialbox\Interfaces\CliCommandInterface;

class InitializeCommand implements CliCommandInterface
{
    /**
     * @inheritDoc
     */
    public static function execute(array $args): int
    {
        if(Configuration::getInstanceConfiguration()->isEnabled() === false && !isset($args['force']))
        {
            $required_configurations = [
                'database.host', 'database.port', 'database.username', 'database.password', 'database.name',
                'instance.enabled', 'instance.domain', 'registration.*'
            ];

            Logger::getLogger()->error('Socialbox is disabled. Use --force to initialize the instance or set `instance.enabled` to True in the configuration');
            Logger::getLogger()->info('The reason you are required to do this is to allow you to configure the instance before enabling it');
            Logger::getLogger()->info('The following configurations are required to be set before enabling the instance:');
            foreach($required_configurations as $config)
            {
                Logger::getLogger()->info(sprintf('  - %s', $config));
            }

            Logger::getLogger()->info('instance.private_key & instance.public_key are automatically generated if not set');
            Logger::getLogger()->info('instance.domain is required to be set to the domain name of the instance');
            Logger::getLogger()->info('instance.rpc_endpoint is required to be set to the publicly accessible http rpc endpoint of this server');
            Logger::getLogger()->info('registration.* are required to be set to allow users to register to the instance');
            Logger::getLogger()->info('You will be given a DNS TXT record to set for the public key after the initialization process');
            Logger::getLogger()->info('The configuration file can be edited using ConfigLib:');
            Logger::getLogger()->info('  configlib --conf socialbox -e nano');
            Logger::getLogger()->info('Or manually at:');
            Logger::getLogger()->info(sprintf('  %s', Configuration::getConfigurationLib()->getPath()));
            return 1;
        }

        if(Configuration::getInstanceConfiguration()->getDomain() === null)
        {
            Logger::getLogger()->error('instance.domain is required but was not set');
            return 1;
        }

        if(Configuration::getInstanceConfiguration()->getRpcEndpoint() === null)
        {
            Logger::getLogger()->error('instance.rpc_endpoint is required but was not set');
            return 1;
        }

        Logger::getLogger()->info('Initializing Socialbox...');
        if(Configuration::getCacheConfiguration()->isEnabled())
        {
            Logger::getLogger()->verbose('Clearing cache layer...');
            CacheLayer::getInstance()->clear();
        }

        foreach(DatabaseObjects::casesOrdered() as $object)
        {
            Logger::getLogger()->verbose("Initializing database object {$object->value}");

            try
            {
                Database::getConnection()->exec(file_get_contents(Resources::getDatabaseResource($object)));
            }
            catch (PDOException $e)
            {
                // Check if the error code is for "table already exists"
                if ($e->getCode() === '42S01')
                {
                    Logger::getLogger()->warning("Database object {$object->value} already exists, skipping...");
                    continue;
                }
                else
                {
                    Logger::getLogger()->error("Failed to initialize database object {$object->value}: {$e->getMessage()}", $e);
                    return 1;
                }
            }
            catch(Exception $e)
            {
                Logger::getLogger()->error("Failed to initialize database object {$object->value}: {$e->getMessage()}", $e);
                return 1;
            }
        }

        if(!Configuration::getInstanceConfiguration()->getPublicKey() || !Configuration::getInstanceConfiguration()->getPrivateKey())
        {
            try
            {
                Logger::getLogger()->info('Generating new key pair...');
                $keyPair = Cryptography::generateKeyPair();
            }
            catch (CryptographyException $e)
            {
                Logger::getLogger()->error('Failed to generate keypair', $e);
                return 1;
            }

            Logger::getLogger()->info('Updating configuration...');
            Configuration::getConfigurationLib()->set('instance.private_key', $keyPair->getPrivateKey());
            Configuration::getConfigurationLib()->set('instance.public_key', $keyPair->getPublicKey());
            Configuration::getConfigurationLib()->save();

            Logger::getLogger()->info(sprintf('Set the DNS TXT record for the domain %s to the following value:', Configuration::getInstanceConfiguration()->getDomain()));
            Logger::getLogger()->info(sprintf("v=socialbox;sb-rpc=%s;sb-key=%s;",
                Configuration::getInstanceConfiguration()->getRpcEndpoint(), $keyPair->getPublicKey()
            ));
        }

        // TODO: Create a host peer here?
        Logger::getLogger()->info('Socialbox has been initialized successfully');
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