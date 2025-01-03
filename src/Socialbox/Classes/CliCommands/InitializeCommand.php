<?php

    namespace Socialbox\Classes\CliCommands;

    use Exception;
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
    use Socialbox\Socialbox;

    class InitializeCommand implements CliCommandInterface
    {
        /**
         * @inheritDoc
         */
        public static function execute(array $args): int
        {
            if(Configuration::getInstanceConfiguration()->isEnabled() === false && !isset($args['force']) && getenv('SB_MODE') !== 'automated')
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
                Logger::getLogger()->info('Automated Setup Procedure is done using environment variables:');
                Logger::getLogger()->info(' - SB_MODE=automated');
                Logger::getLogger()->info(' - SB_INSTANCE_DOMAIN=example.com => The Domain Name');
                Logger::getLogger()->info(' - SB_INSTANCE_RPC_ENDPOINT=http://localhost => The RPC Endpoint, must be publicly accessible');
                Logger::getLogger()->info(' - SB_DATABASE_HOST=localhost => The MySQL Host');
                Logger::getLogger()->info(' - SB_DATABASE_PORT=3306 => The MySQL Port');
                Logger::getLogger()->info(' - SB_DATABASE_USER=root => The MySQL Username');
                Logger::getLogger()->info(' - SB_DATABASE_PASSWORD=pass => The MySQL Password');
                Logger::getLogger()->info(' - SB_DATABASE_DATABASE=socialbox => The MySQL Database');
                Logger::getLogger()->info(' - SB_CACHE_ENGINE=redis => The Cache engine to use, supports redis, memcached or null');
                Logger::getLogger()->info(' - SB_CACHE_HOST=localhost => The Cache Host');
                Logger::getLogger()->info(' - SB_CACHE_PORT=6379 => The Cache Port');
                Logger::getLogger()->info(' - SB_CACHE_PASSWORD=pass => The Cache Password');
                Logger::getLogger()->info(' - SB_CACHE_DATABASE=0 => The Cache Database');
                Logger::getLogger()->info(' - SB_STORAGE_PATH=/etc/socialbox => The Storage Path');
                Logger::getLogger()->info('Anything omitted will be null or empty in the configuration');

                return 1;
            }

            // Overwrite the configuration if the automated setup procedure is detected
            // This is useful for CI/CD pipelines & Docker
            if(getenv('SB_MODE') === 'automated')
            {
                Logger::getLogger()->info('Automated Setup Procedure is detected');

                if(getenv('SB_INSTANCE_DOMAIN') !== false)
                {
                    Configuration::getConfigurationLib()->set('instance.domain', getenv('SB_INSTANCE_DOMAIN'));
                    Logger::getLogger()->info('Set instance.domain to ' . getenv('SB_INSTANCE_DOMAIN'));
                }
                else
                {
                    Logger::getLogger()->warning('instance.domain is required but was not set, expected SB_INSTANCE_DOMAIN environment variable');
                }

                if(getenv('SB_INSTANCE_RPC_ENDPOINT') !== false)
                {
                    Configuration::getConfigurationLib()->set('instance.rpc_endpoint', getenv('SB_INSTANCE_RPC_ENDPOINT'));
                    Logger::getLogger()->info('Set instance.rpc_endpoint to ' . getenv('SB_INSTANCE_RPC_ENDPOINT'));
                }
                else
                {
                    Logger::getLogger()->warning('instance.rpc_endpoint is required but was not set, expected SB_INSTANCE_RPC_ENDPOINT environment variable');
                    Configuration::getConfigurationLib()->set('instance.rpc_endpoint', 'http://127.0.0.0/');
                    Logger::getLogger()->info('Set instance.rpc_endpoint to http://127.0.0.0/');
                }

                if(getenv('SB_STORAGE_PATH') !== false)
                {
                    Configuration::getConfigurationLib()->set('storage.path', getenv('SB_STORAGE_PATH'));
                    Logger::getLogger()->info('Set storage.path to ' . getenv('SB_STORAGE_PATH'));
                }
                else
                {
                    Configuration::getConfigurationLib()->set('storage.path', '/etc/socialbox');
                    Logger::getLogger()->info('storage.path was not set, defaulting to /etc/socialbox');
                }

                if(getenv('SB_DATABASE_HOST') !== false)
                {
                    Configuration::getConfigurationLib()->set('database.host', getenv('SB_DATABASE_HOST'));
                    Logger::getLogger()->info('Set database.host to ' . getenv('SB_DATABASE_HOST'));
                }
                else
                {
                    Logger::getLogger()->warning('database.host is required but was not set, expected SB_DATABASE_HOST environment variable');
                }

                if(getenv('SB_DATABASE_PORT') !== false)
                {
                    Configuration::getConfigurationLib()->set('database.port', getenv('SB_DATABASE_PORT'));
                    Logger::getLogger()->info('Set database.port to ' . getenv('SB_DATABASE_PORT'));
                }

                if(getenv('SB_DATABASE_USERNAME') !== false)
                {
                    Configuration::getConfigurationLib()->set('database.username', getenv('SB_DATABASE_USERNAME'));
                    Logger::getLogger()->info('Set database.username to ' . getenv('SB_DATABASE_USERNAME'));
                }
                else
                {
                    Logger::getLogger()->warning('database.username is required but was not set, expected SB_DATABASE_USERNAME environment variable');
                }

                if(getenv('SB_DATABASE_PASSWORD') !== false)
                {
                    Configuration::getConfigurationLib()->set('database.password', getenv('SB_DATABASE_PASSWORD'));
                    Logger::getLogger()->info('Set database.password to ' . getenv('SB_DATABASE_PASSWORD'));
                }
                else
                {
                    Logger::getLogger()->warning('database.password is required but was not set, expected SB_DATABASE_PASSWORD environment variable');
                }

                if(getenv('SB_DATABASE_NAME') !== false)
                {
                    Configuration::getConfigurationLib()->set('database.name', getenv('SB_DATABASE_NAME'));
                    Logger::getLogger()->info('Set database.name to ' . getenv('SB_DATABASE_NAME'));
                }
                else
                {
                    Logger::getLogger()->warning('database.name is required but was not set, expected SB_DATABASE_NAME environment variable');
                }

                if(getenv('SB_CACHE_ENABLED') !== false)
                {
                    Configuration::getConfigurationLib()->set('cache.enabled', true);
                    Logger::getLogger()->info('Set cache.engine to true');
                }
                else
                {
                    Configuration::getConfigurationLib()->set('cache.enabled', false);
                    Logger::getLogger()->info('cache.engine is was not set, defaulting to false');
                }


                if(getenv('SB_CACHE_ENGINE') !== false)
                {
                    Configuration::getConfigurationLib()->set('cache.engine', getenv('SB_CACHE_ENGINE'));
                    Logger::getLogger()->info('Set cache.engine to ' . getenv('SB_CACHE_ENGINE'));
                }

                if(getenv('SB_CACHE_HOST') !== false)
                {
                    Configuration::getConfigurationLib()->set('cache.host', getenv('SB_CACHE_HOST'));
                    Logger::getLogger()->info('Set cache.host to ' . getenv('SB_CACHE_HOST'));
                }
                elseif(Configuration::getCacheConfiguration()->isEnabled())
                {
                    Logger::getLogger()->warning('cache.host is required but was not set, expected SB_CACHE_HOST environment variable');
                }

                if(getenv('SB_CACHE_PORT') !== false)
                {
                    Configuration::getConfigurationLib()->set('cache.port', getenv('SB_CACHE_PORT'));
                    Logger::getLogger()->info('Set cache.port to ' . getenv('SB_CACHE_PORT'));
                }

                if(getenv('SB_CACHE_PASSWORD') !== false)
                {
                    Configuration::getConfigurationLib()->set('cache.password', getenv('SB_CACHE_PASSWORD'));
                    Logger::getLogger()->info('Set cache.password to ' . getenv('SB_CACHE_PASSWORD'));
                }
                elseif(Configuration::getCacheConfiguration()->isEnabled())
                {
                    Logger::getLogger()->warning('cache.password is required but was not set, expected SB_CACHE_PASSWORD environment variable');
                }

                if(getenv('SB_CACHE_DATABASE') !== false)
                {
                    Configuration::getConfigurationLib()->set('cache.database', getenv('SB_CACHE_DATABASE'));
                    Logger::getLogger()->info('Set cache.database to ' . getenv('SB_CACHE_DATABASE'));
                }
                elseif(Configuration::getCacheConfiguration()->isEnabled())
                {
                    Configuration::getConfigurationLib()->set('cache.database', 0);
                    Logger::getLogger()->info('cache.database defaulting to 0');
                }

                Logger::getLogger()->info('Updating configuration...');
                Configuration::getConfigurationLib()->save(); // Save
                Configuration::reload(); // Reload
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

            if(
                !Configuration::getCryptographyConfiguration()->getHostPublicKey() ||
                !Configuration::getCryptographyConfiguration()->getHostPrivateKey() ||
                !Configuration::getCryptographyConfiguration()->getHostPublicKey()
            )
            {
                $expires = time() + 31536000;

                try
                {
                    Logger::getLogger()->info('Generating new key pair (expires ' . date('Y-m-d H:i:s', $expires) . ')...');
                    $signingKeyPair = Cryptography::generateSigningKeyPair();
                }
                catch (CryptographyException $e)
                {
                    Logger::getLogger()->error('Failed to generate cryptography values', $e);
                    return 1;
                }

                Configuration::getConfigurationLib()->set('cryptography.host_keypair_expires', $expires);
                Configuration::getConfigurationLib()->set('cryptography.host_private_key', $signingKeyPair->getPrivateKey());
                Configuration::getConfigurationLib()->set('cryptography.host_public_key', $signingKeyPair->getPublicKey());
            }

            // If Internal Encryption keys are null or has less keys than configured, populate the configuration
            // property with encryption keys.
            if(
                Configuration::getCryptographyConfiguration()->getInternalEncryptionKeys() === null ||
                count(Configuration::getCryptographyConfiguration()->getInternalEncryptionKeys()) < Configuration::getCryptographyConfiguration()->getEncryptionKeysCount())
            {
                Logger::getLogger()->info('Generating internal encryption keys...');
                $encryptionKeys = Configuration::getCryptographyConfiguration()->getInternalEncryptionKeys() ?? [];
                while(count($encryptionKeys) < Configuration::getCryptographyConfiguration()->getEncryptionKeysCount())
                {
                    $encryptionKeys[] = Cryptography::generateEncryptionKey(Configuration::getCryptographyConfiguration()->getEncryptionKeysAlgorithm());
                }

                Configuration::getConfigurationLib()->set('cryptography.internal_encryption_keys', $encryptionKeys);
            }

            Logger::getLogger()->info('Updating configuration...');
            Configuration::getConfigurationLib()->save();;
            Configuration::reload();

            Logger::getLogger()->info('Socialbox has been initialized successfully');
            Logger::getLogger()->info(sprintf('Set the DNS TXT record for the domain %s to the following value:', Configuration::getInstanceConfiguration()->getDomain()));
            Logger::getLogger()->info(Socialbox::getDnsRecord());

            if(getenv('SB_MODE') === 'automated')
            {
                Configuration::getConfigurationLib()->set('instance.enabled', true);
                Configuration::getConfigurationLib()->save(); // Save

                Logger::getLogger()->info('Automated Setup Procedure is complete, requests to the RPC server ' . Configuration::getInstanceConfiguration()->getRpcEndpoint() . ' are now accepted');
            }

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