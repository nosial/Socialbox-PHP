<?php

    namespace Socialbox\Classes\CliCommands;

    use Exception;
    use ncc\ThirdParty\Symfony\Process\Exception\InvalidArgumentException;
    use PDOException;
    use Socialbox\Abstracts\CacheLayer;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Classes\Database;
    use Socialbox\Classes\DnsHelper;
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

                if(getenv('SB_MODE') === 'automated')
                {
                    // Wait & Reload the configuration
                    while(!Configuration::getInstanceConfiguration()->isEnabled())
                    {
                        Logger::getLogger()->info('Waiting for configuration, retrying in 5 seconds...');
                        sleep(5);
                        Configuration::reload();
                    }
                }
                else
                {
                    return 1;
                }

                return 1;
            }

            // Overwrite the configuration if the automated setup procedure is detected
            // This is useful for CI/CD pipelines & Docker
            if(getenv('SB_MODE') === 'automated')
            {
                Logger::getLogger()->info('Automated Setup Procedure is detected');
                self::applyEnvironmentVariables();
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
            Configuration::getConfigurationLib()->save();
            Configuration::reload();

            Logger::getLogger()->info('Socialbox has been initialized successfully');
            Logger::getLogger()->info(sprintf('Set the DNS TXT record for the domain %s to the following value:', Configuration::getInstanceConfiguration()->getDomain()));
            Logger::getLogger()->info(Socialbox::getDnsRecord());

            return 0;
        }

        /**
         * Applies environment variables to the application's configuration system.
         * This method maps predefined environment variables to their corresponding
         * configuration keys, validates their values, and updates the configuration
         * library accordingly. If expected environment variables are missing and
         * critical for certain components, warning logs are generated.
         * Additionally, the configuration changes are saved and reloaded after being applied.
         *
         * @return void
         */
        private static function applyEnvironmentVariables(): void
        {
            // Always set the 'instance.enabled' to true if the automated setup procedure is detected
            Configuration::getConfigurationLib()->set('instance.enabled', true);
            $configurationMap = [
                // Instance Configuration
                'SB_INSTANCE_NAME' => 'instance.name',
                'SB_INSTANCE_DOMAIN' => 'instance.domain',
                'SB_INSTANCE_RPC_ENDPOINT' => 'instance.rpc_endpoint',
                'SB_STORAGE_PATH' => 'storage.path',

                // Logging Configuration
                'SB_LOGGING_CONSOLE_ENABLED' => 'logging.console_logging_enabled',
                'SB_LOGGING_CONSOLE_LEVEL' => 'logging.console_logging_level',
                'SB_LOGGING_FILE_ENABLED' => 'logging.file_logging_enabled',
                'SB_LOGGING_FILE_LEVEL' => 'logging.file_logging_level',

                // Security & Cryptography Configuration
                'SB_SECURITY_DISPLAY_INTERNAL_EXCEPTIONS' => 'security.display_internal_exceptions',
                'SB_CRYPTO_KEYPAIR_EXPIRES' => 'cryptography.host_keypair_expires',
                'SB_CRYPTO_ENCRYPTION_KEYS_COUNT' => 'cryptography.encryption_keys_count',
                'SB_CRYPTO_ENCRYPTION_KEYS_ALGORITHM' => 'cryptography.encryption_keys_algorithm',
                'SB_CRYPTO_TRANSPORT_ENCRYPTION_ALGORITHM' => 'cryptography.transport_encryption_algorithm',

                // Database Configuration
                'SB_DATABASE_HOST' => 'database.host',
                'SB_DATABASE_PORT' => 'database.port',
                'SB_DATABASE_USERNAME' => 'database.username',
                'SB_DATABASE_PASSWORD' => 'database.password',
                'SB_DATABASE_NAME' => 'database.name',

                'SB_CACHE_ENABLED' => 'cache.enabled',
                'SB_CACHE_ENGINE' => 'cache.engine',
                'SB_CACHE_HOST' => 'cache.host',
                'SB_CACHE_PORT' => 'cache.port',
                'SB_CACHE_USERNAME' => 'cache.username',
                'SB_CACHE_PASSWORD' => 'cache.password',
                'SB_CACHE_DATABASE' => 'cache.database',
            ];

            foreach($configurationMap as $env => $config)
            {
                $variable = getenv($env);
                Logger::getLogger()->info(sprintf('Checking environment variable %s...', $env));

                switch($env)
                {
                    case 'SB_STORAGE_PATH':
                    case 'SB_LOGGING_FILE_LEVEL':
                    case 'SB_LOGGING_CONSOLE_LEVEL':
                    case 'SB_INSTANCE_NAME':
                    case 'SB_CRYPTO_ENCRYPTION_KEYS_ALGORITHM':
                    case 'SB_CRYPTO_TRANSPORT_ENCRYPTION_ALGORITHM':
                    case 'SB_CACHE_ENGINE':
                    case 'SB_CACHE_HOST':
                    case 'SB_CACHE_USERNAME':
                    case 'SB_CACHE_PASSWORD':
                    case 'SB_CACHE_DATABASE':
                        if($variable !== false)
                        {
                            Configuration::getConfigurationLib()->set($config, $variable);
                            Logger::getLogger()->info(sprintf('Set %s to %s', $config, $variable));
                        }
                        break;

                    case 'SB_INSTANCE_DOMAIN':
                        if($variable === false && Configuration::getInstanceConfiguration()->getDomain() === null)
                        {
                            Logger::getLogger()->warning(sprintf('%s is not set, expected %s environment variable', $config, $env));
                        }
                        else
                        {
                            Configuration::getConfigurationLib()->set($config, $variable);
                            Logger::getLogger()->info(sprintf('Set %s to %s', $config, $variable));
                        }
                        break;

                    case 'SB_DATABASE_HOST':
                        if($variable === false && Configuration::getDatabaseConfiguration()->getHost() === null)
                        {
                            Logger::getLogger()->warning(sprintf('%s is not set, expected %s environment variable', $config, $env));
                        }
                        else
                        {
                            Configuration::getConfigurationLib()->set($config, $variable);
                            Logger::getLogger()->info(sprintf('Set %s to %s', $config, $variable));
                        }
                        break;

                    case 'SB_DATABASE_PORT':
                        if($variable === false && Configuration::getDatabaseConfiguration()->getPort() === null)
                        {
                            Logger::getLogger()->warning(sprintf('%s is not set, expected %s environment variable', $config, $env));
                        }
                        else
                        {
                            Configuration::getConfigurationLib()->set($config, (int) $variable);
                            Logger::getLogger()->info(sprintf('Set %s to %s', $config, $variable));
                        }
                        break;

                    case 'SB_DATABASE_USERNAME':
                        if($variable === false && Configuration::getDatabaseConfiguration()->getUsername() === null)
                        {
                            Logger::getLogger()->warning(sprintf('%s is not set, expected %s environment variable', $config, $env));
                        }
                        else
                        {
                            Configuration::getConfigurationLib()->set($config, $variable);
                            Logger::getLogger()->info(sprintf('Set %s to %s', $config, $variable));
                        }
                        break;

                    case 'SB_DATABASE_PASSWORD':
                        if($variable === false && Configuration::getDatabaseConfiguration()->getPassword() === null)
                        {
                            Logger::getLogger()->warning(sprintf('%s is not set, expected %s environment variable', $config, $env));
                        }
                        else
                        {
                            Configuration::getConfigurationLib()->set($config, $variable);
                            Logger::getLogger()->info(sprintf('Set %s to %s', $config, $variable));
                        }
                        break;

                    case 'SB_DATABASE_NAME':
                        if($variable === false && Configuration::getDatabaseConfiguration()->getName() === null)
                        {
                            Logger::getLogger()->warning(sprintf('%s is not set, expected %s environment variable', $config, $env));
                        }
                        else
                        {
                            Configuration::getConfigurationLib()->set($config, $variable);
                            Logger::getLogger()->info(sprintf('Set %s to %s', $config, $variable));
                        }
                        break;

                    case 'SB_INSTANCE_RPC_ENDPOINT':
                        if($variable === false && Configuration::getInstanceConfiguration()->getRpcEndpoint() === null)
                        {
                            Logger::getLogger()->warning(sprintf('%s is not set, expected %s environment variable', $config, $env));
                        }
                        else
                        {
                            Configuration::getConfigurationLib()->set($config, $variable);
                            Logger::getLogger()->info(sprintf('Set %s to %s', $config, $variable));
                        }
                        break;

                    case 'SB_LOGGING_CONSOLE_ENABLED':
                    case 'SB_SECURITY_DISPLAY_INTERNAL_EXCEPTIONS':
                    case 'SB_LOGGING_FILE_ENABLED':
                    case 'SB_CACHE_ENABLED':
                        if($variable !== false)
                        {
                            Configuration::getConfigurationLib()->set($config, filter_var($variable, FILTER_VALIDATE_BOOLEAN));
                            Logger::getLogger()->info(sprintf('Set %s to %s', $config, $variable));
                        }
                        break;

                    case 'SB_CRYPTO_KEYPAIR_EXPIRES':
                    case 'SB_CRYPTO_ENCRYPTION_KEYS_COUNT':
                    case 'SB_CACHE_PORT':
                        if($variable !== false)
                        {
                            Configuration::getConfigurationLib()->set($config, (int) $variable);
                            Logger::getLogger()->info(sprintf('Set %s to %s', $config, $variable));
                        }
                        break;

                    default:
                        Logger::getLogger()->warning("Environment variable $env is not supported");
                        break;
                }
            }

            // Handle Mock Servers environment variables (SB_INSTANCE_DNS_MOCK_*)
            $mockServers = [];
            foreach(self::getMockServerValues() as $mockServer)
            {
                $mockServer = explode(' ', $mockServer);
                if(count($mockServer) !== 2)
                {
                    Logger::getLogger()->warning(sprintf('Invalid DNS Mock Server format: %s', implode(' ', $mockServer)));
                    continue;
                }

                $domain = $mockServer[0] ?? null;
                $txt = $mockServer[1] ?? null;
                if($domain === null || $txt === null)
                {
                    Logger::getLogger()->warning(sprintf('Invalid DNS Mock Server format, domain or txt missing: %s', implode(' ', $mockServer)));
                    continue;
                }

                try
                {
                    $mockServers[$domain] = $txt;
                    Logger::getLogger()->info(sprintf('Added Mock Server %s: %s', $domain, $txt));
                }
                catch(InvalidArgumentException $e)
                {
                    Logger::getLogger()->error(sprintf('Invalid TXT record format for %s', $domain), $e);
                    continue;
                }
            }

            if(count($mockServers) > 0)
            {
                Logger::getLogger()->info('Setting Mock Servers...');
                Configuration::getConfigurationLib()->set('instance.dns_mocks', $mockServers);
            }

            // Apply changes & reload the configuration
            Logger::getLogger()->info('Updating configuration...');
            Configuration::getConfigurationLib()->save(); // Save
            Configuration::reload(); // Reload
        }

        /**
         * Retrieves all environment variable values that start with the prefix 'SB_INSTANCE_DNS_MOCK_'.
         *
         * @return array An array of environment variable values filtered by the specified prefix.
         */
        private static function getMockServerValues(): array
        {
            // Fetch all environment variables
            $envVars = getenv();

            // Filter variables that start with the specified prefix
            $filtered = array_filter($envVars, function ($key)
            {
                return str_starts_with($key, 'SB_INSTANCE_DNS_MOCK_');
            }, ARRAY_FILTER_USE_KEY);

            // Return only the values as an array
            return array_values($filtered);
        }

        /**
         * @inheritDoc
         */
        public static function getHelpMessage(): string
        {
            return  "Initialize Command - Initializes Socialbox for first-runs\n" .
                    "Usage: socialbox init [arguments]\n\n" .
                    "Arguments:\n" .
                    "  --force - Forces the initialization process to run even the instance is disabled\n\n" .
                    "Environment Variables:\n"  .
                    "  SB_MODE - Set to 'automated' to enable automated setup procedure (Must be set to enable environment variables)\n" .
                    "  SB_INSTANCE_DOMAIN - The domain name of the instance (eg; Socialbox)\n" .
                    "  SB_INSTANCE_RPC_ENDPOINT - The public RPC endpoint of the instance (eg; https://rpc.teapot.com/)\n" .
                    "  SB_STORAGE_PATH - The path to store files (default: /etc/socialbox)\n" .
                    "  SB_LOGGING_CONSOLE_ENABLED - Enable console logging (default: true)\n" .
                    "  SB_LOGGING_CONSOLE_LEVEL - Console logging level (default: info)\n" .
                    "  SB_LOGGING_FILE_ENABLED - Enable file logging (default: true)\n" .
                    "  SB_LOGGING_FILE_LEVEL - File logging level (default: error)\n" .
                    "  SB_SECURITY_DISPLAY_INTERNAL_EXCEPTIONS - Display internal exceptions (default: false)\n" .
                    "  SB_CRYPTO_KEYPAIR_EXPIRES - The expiration date of the key pair in Unix timestamp (default: current time + 1 year)\n" .
                    "  SB_CRYPTO_ENCRYPTION_KEYS_COUNT - The number of internal encryption keys to generate (default: 5)\n" .
                    "  SB_CRYPTO_ENCRYPTION_KEYS_ALGORITHM - The algorithm to use for encryption keys (default: xchacha20)\n" .
                    "  SB_CRYPTO_TRANSPORT_ENCRYPTION_ALGORITHM - The algorithm to use for transport encryption (default: chacha20)\n" .
                    "  SB_DATABASE_HOST - The database host (default: localhost)\n" .
                    "  SB_DATABASE_PORT - The database port (default: 3306)\n" .
                    "  SB_DATABASE_USERNAME - The database username (default: root)\n" .
                    "  SB_DATABASE_PASSWORD - The database password (default: null)\n" .
                    "  SB_DATABASE_NAME - The database name (default: socialbox)\n" .
                    "  SB_CACHE_ENABLED - Enable cache layer (default: false)\n" .
                    "  SB_CACHE_ENGINE - The cache engine to use (default: redis)\n" .
                    "  SB_CACHE_HOST - The cache host (default: localhost)\n" .
                    "  SB_CACHE_PORT - The cache port (default: 6379)\n" .
                    "  SB_CACHE_USERNAME - The cache username (default: null)\n" .
                    "  SB_CACHE_PASSWORD - The cache password (default: null)\n" .
                    "  SB_CACHE_DATABASE - The cache database (default: 0)\n" .
                    "  SB_INSTANCE_DNS_MOCK_* - Mock server environment variables, format: (<domain> <txt>), eg; SB_INSTANCE_DNS_MOCK_N64: teapot.com <txt>\n";
        }

        /**
         * @inheritDoc
         */
        public static function getShortHelpMessage(): string
        {
            return "Initializes Socialbox for first-runs";
        }
    }