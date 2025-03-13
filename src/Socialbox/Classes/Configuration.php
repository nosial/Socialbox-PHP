<?php

    namespace Socialbox\Classes;

    use Socialbox\Classes\Configuration\AuthenticationConfiguration;
    use Socialbox\Classes\Configuration\CacheConfiguration;
    use Socialbox\Classes\Configuration\CryptographyConfiguration;
    use Socialbox\Classes\Configuration\DatabaseConfiguration;
    use Socialbox\Classes\Configuration\InstanceConfiguration;
    use Socialbox\Classes\Configuration\LoggingConfiguration;
    use Socialbox\Classes\Configuration\PoliciesConfiguration;
    use Socialbox\Classes\Configuration\RegistrationConfiguration;
    use Socialbox\Classes\Configuration\SecurityConfiguration;
    use Socialbox\Classes\Configuration\StorageConfiguration;

    class Configuration
    {
        private static ?\ConfigLib\Configuration $configuration = null;
        private static ?InstanceConfiguration $instanceConfiguration = null;
        private static ?SecurityConfiguration $securityConfiguration = null;
        private static ?CryptographyConfiguration $cryptographyConfiguration = null;
        private static ?DatabaseConfiguration $databaseConfiguration = null;
        private static ?LoggingConfiguration $loggingConfiguration = null;
        private static ?CacheConfiguration $cacheConfiguration = null;
        private static ?RegistrationConfiguration $registrationConfiguration = null;
        private static ?AuthenticationConfiguration $authenticationConfiguration = null;
        private static ?PoliciesConfiguration $policiesConfiguration = null;
        private static ?StorageConfiguration $storageConfiguration = null;

        /**
         * Initializes the configuration settings for the application. This includes
         * settings for the instance, security, database, cache layer, and registration.
         *
         * @return void
         */
        private static function initializeConfiguration(): void
        {
            $config = new \ConfigLib\Configuration('socialbox');

            // Instance configuration
            $config->setDefault('instance.enabled', false); // False by default, requires the user to enable it.
            $config->setDefault('instance.name', "Socialbox Server");
            $config->setDefault('instance.domain', null);
            $config->setDefault('instance.rpc_endpoint', null);
            // DNS Mocking Configuration, usually used for testing purposes
            // Allows the user to mock a domain to use a specific TXT record
            $config->setDefault('instance.dns_mocks', []);

            // Security Configuration
            $config->setDefault('security.display_internal_exceptions', false);
            $config->setDefault('security.resolved_servers_ttl', 600);
            $config->setDefault('security.captcha_ttl', 200);
            // Server-side OTP Security options
            // The time step in seconds for the OTP generation
            // Default: 30 seconds
            $config->setDefault('security.otp_secret_key_length', 32);
            $config->setDefault('security.otp_time_step', 30);
            // The number of digits in the OTP
            $config->setDefault('security.otp_digits', 6);
            // The hash algorithm to use for the OTP generation (sha1, sha256, sha512)
            $config->setDefault('security.otp_hash_algorithm', 'sha512');
            // The window of time steps to allow for OTP verification
            $config->setDefault('security.otp_window', 1);

            // Cryptography Configuration
            // The Unix Timestamp for when the host's keypair should expire
            // Setting this value to 0 means the keypair never expires
            // Setting this value to null will automatically set the current unix timestamp + 1 year as the value
            // This means at initialization, the key is automatically set to expire in a year.
            $config->setDefault('cryptography.host_keypair_expires', null);
            // The host's public/private keypair in base64 encoding, when null; the initialization process
            // will automatically generate a new keypair
            $config->setDefault('cryptography.host_public_key', null);
            $config->setDefault('cryptography.host_private_key', null);

            // The internal encryption keys used for encrypting data in the database when needed.
            // When null, the initialization process will automatically generate a set of keys
            // based on the `encryption_keys_count` and `encryption_keys_algorithm` configuration.
            // This is an array of base64 encoded keys.
            $config->setDefault('cryptography.internal_encryption_keys', null);

            // The number of encryption keys to generate and set to `instance.encryption_keys` this will be used
            // to randomly encrypt/decrypt sensitive data in the database, this includes hashes.
            // The higher the number the higher performance impact it will have on the server
            $config->setDefault('cryptography.encryption_keys_count', 10);
            // The host's encryption algorithm, this will be used to generate a set of encryption keys
            // This is for internal encryption, these keys are never shared outside this configuration.
            // Recommendation: Higher security over performance
            $config->setDefault('cryptography.encryption_keys_algorithm', 'xchacha20');

            // The encryption algorithm to use for encrypted message transport between the client aand the server
            // This is the encryption the server tells the client to use and the client must support it.
            // Recommendation: Good balance between security and performance
            // For universal support & performance, use aes256gcm for best performance or for best security use xchacha20
            $config->setDefault('cryptography.transport_encryption_algorithm', 'chacha20');

            // Database configuration
            $config->setDefault('database.host', '127.0.0.1');
            $config->setDefault('database.port', 3306);
            $config->setDefault('database.username', 'root');
            $config->setDefault('database.password', 'root');
            $config->setDefault('database.name', 'test');

            // Logging configuration
            $config->setDefault('logging.console_logging_enabled', true);
            $config->setDefault('logging.console_logging_level', 'info');
            $config->setDefault('logging.file_logging_enabled', true);
            $config->setDefault('logging.file_logging_level', 'error');

            // Cache layer configuration
            $config->setDefault('cache.enabled', false);
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
            $config->setDefault('registration.privacy_policy_document', null);
            $config->setDefault('registration.privacy_policy_date', 1734985525);
            $config->setDefault('registration.accept_privacy_policy', true);
            $config->setDefault('registration.terms_of_service_document', null);
            $config->setDefault('registration.terms_of_service_date', 1734985525);
            $config->setDefault('registration.accept_terms_of_service', true);
            $config->setDefault('registration.community_guidelines_document', null);
            $config->setDefault('registration.community_guidelines_date', 1734985525);
            $config->setDefault('registration.accept_community_guidelines', true);
            $config->setDefault('registration.password_required', true);
            $config->setDefault('registration.otp_required', false);
            $config->setDefault('registration.display_name_required', true);
            $config->setDefault('registration.first_name_required', false);
            $config->setDefault('registration.middle_name_required', false);
            $config->setDefault('registration.last_name_required', false);
            $config->setDefault('registration.display_picture_required', false);
            $config->setDefault('registration.email_address_required', false);
            $config->setDefault('registration.phone_number_required', false);
            $config->setDefault('registration.birthday_required', false);
            $config->setDefault('registration.url_required', false);
            $config->setDefault('registration.image_captcha_verification_required', true);

            // Authentication configuration
            $config->setDefault('authentication.enabled', true);
            $config->setDefault('authentication.image_captcha_verification_required', true);

            // Server Policies
            // The maximum number of signing keys a peer can register onto the server at once
            $config->setDefault('policies.max_signing_keys', 20);
            $config->setDefault('policies.max_contact_signing_keys', 50);
            // The amount of time in seconds it takes before a session is considered expired due to inactivity
            // Default: 12hours
            $config->setDefault('policies.session_inactivity_expires', 43200);
            // The amount of time in seconds it takes before an image captcha is considered expired due to lack of
            // answer within the time-frame that the captcha was generated
            // If expired; client is expected to request for a new captcha which will generate a new random answer.
            $config->setDefault('policies.image_captcha_expires', 300);
            // The amount of time in seconds it takes before a peer's external address is resolved again
            // When a peer's external address is resolved, it is cached for this amount of time before resolving again.
            // This reduces the amount of times a resolution request is made to the external server.
            $config->setDefault('policies.peer_sync_interval', 3600);
            // The maximum number of contacts a peer can retrieve from the server at once, if the client puts a
            // value that exceeds this limit, the server will use this limit instead.
            // recommendation: 100
            $config->setDefault('policies.get_contacts_limit', 100);
            $config->setDefault('policies.get_encryption_channel_requests_limit', 100);
            $config->setDefault('policies.get_encryption_channels_limit', 100);
            $config->setDefault('policies.get_encryption_channel_incoming_limit', 100);
            $config->setDefault('policies.get_encryption_channel_outgoing_limit', 100);
            $config->setDefault('policies.encryption_channel_max_messages', 100);

            // Default privacy states for information fields associated with the peer
            $config->setDefault('policies.default_display_picture_privacy', 'PUBLIC');
            $config->setDefault('policies.default_first_name_privacy', 'CONTACTS');
            $config->setDefault('policies.default_middle_name_privacy', 'PRIVATE');
            $config->setDefault('policies.default_last_name_privacy', 'PRIVATE');
            $config->setDefault('policies.default_email_address_privacy', 'CONTACTS');
            $config->setDefault('policies.default_phone_number_privacy', 'CONTACTS');
            $config->setDefault('policies.default_birthday_privacy', 'PRIVATE');
            $config->setDefault('policies.default_url_privacy', 'PUBLIC');

            // Storage configuration
            $config->setDefault('storage.path', '/etc/socialbox'); // The main path for file storage
            $config->setDefault('storage.user_display_images_path', 'user_profiles'); // eg; `/etc/socialbox/user_profiles`
            $config->setDefault('storage.user_display_images_max_size', 3145728); // 3MB

            $config->save();

            self::$configuration = $config;
            self::$instanceConfiguration = new InstanceConfiguration(self::$configuration->getConfiguration()['instance']);
            self::$securityConfiguration = new SecurityConfiguration(self::$configuration->getConfiguration()['security']);
            self::$cryptographyConfiguration = new CryptographyConfiguration(self::$configuration->getConfiguration()['cryptography']);
            self::$databaseConfiguration = new DatabaseConfiguration(self::$configuration->getConfiguration()['database']);
            self::$loggingConfiguration = new LoggingConfiguration(self::$configuration->getConfiguration()['logging']);
            self::$cacheConfiguration = new CacheConfiguration(self::$configuration->getConfiguration()['cache']);
            self::$registrationConfiguration = new RegistrationConfiguration(self::$configuration->getConfiguration()['registration']);
            self::$authenticationConfiguration = new AuthenticationConfiguration(self::$configuration->getConfiguration()['authentication']);
            self::$policiesConfiguration = new PoliciesConfiguration(self::$configuration->getConfiguration()['policies']);
            self::$storageConfiguration = new StorageConfiguration(self::$configuration->getConfiguration()['storage']);
        }

        /**
         * Resets all configuration instances by setting them to null and then
         * reinitializes the configurations.
         *
         * @return void
         */
        public static function reload(): void
        {
            self::$configuration = null;
            self::$instanceConfiguration = null;
            self::$securityConfiguration = null;
            self::$databaseConfiguration = null;
            self::$loggingConfiguration = null;
            self::$cacheConfiguration = null;
            self::$registrationConfiguration = null;

            self::initializeConfiguration();
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

            return self::$configuration->getConfiguration();
        }

        /**
         * Retrieves the configuration library instance.
         *
         * This method returns the current Configuration instance from the ConfigLib namespace.
         * If the configuration has not been initialized yet, it initializes it first.
         *
         * @return \ConfigLib\Configuration The configuration library instance.
         */
        public static function getConfigurationLib(): \ConfigLib\Configuration
        {
            if(self::$configuration === null)
            {
                self::initializeConfiguration();
            }

            return self::$configuration;
        }

        /**
         * Retrieves the current instance configuration.
         *
         * @return InstanceConfiguration The current instance configuration instance.
         */
        public static function getInstanceConfiguration(): InstanceConfiguration
        {
            if(self::$instanceConfiguration === null)
            {
                self::initializeConfiguration();
            }

            return self::$instanceConfiguration;
        }

        /**
         * Retrieves the current security configuration.
         *
         * @return SecurityConfiguration The current security configuration instance.
         */
        public static function getSecurityConfiguration(): SecurityConfiguration
        {
            if(self::$securityConfiguration === null)
            {
                self::initializeConfiguration();
            }

            return self::$securityConfiguration;
        }

        /**
         * Retrieves the cryptography configuration.
         *
         * This method returns the current CryptographyConfiguration instance.
         * If the configuration has not been initialized yet, it initializes it first.
         *
         * @return CryptographyConfiguration|null The cryptography configuration instance or null if not available.
         */
        public static function getCryptographyConfiguration(): ?CryptographyConfiguration
        {
            if(self::$cryptographyConfiguration === null)
            {
                self::initializeConfiguration();
            }

            return self::$cryptographyConfiguration;
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
         * Retrieves the current logging configuration.
         *
         * @return LoggingConfiguration The current logging configuration instance.
         */
        public static function getLoggingConfiguration(): LoggingConfiguration
        {
            if(self::$loggingConfiguration === null)
            {
                self::initializeConfiguration();
            }

            return self::$loggingConfiguration;
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

        /**
         * Retrieves the authentication configuration.
         *
         * This method returns the current AuthenticationConfiguration instance.
         * If the configuration has not been initialized yet, it initializes it first.
         *
         * @return AuthenticationConfiguration The authentication configuration instance.
         */
        public static function getAuthenticationConfiguration(): AuthenticationConfiguration
        {
            if(self::$authenticationConfiguration === null)
            {
                self::initializeConfiguration();
            }

            return self::$authenticationConfiguration;
        }

        /**
         * Retrieves the policies configuration.
         *
         * This method returns the current PoliciesConfiguration instance.
         * If the configuration has not been initialized yet, it initializes it first.
         *
         * @return PoliciesConfiguration The policies configuration instance.
         */
        public static function getPoliciesConfiguration(): PoliciesConfiguration
        {
            if(self::$policiesConfiguration === null)
            {
                self::initializeConfiguration();
            }

            return self::$policiesConfiguration;
        }

        /**
         * Retrieves the storage configuration.
         *
         * This method returns the current StorageConfiguration instance.
         * If the configuration has not been initialized yet, it initializes it first.
         *
         * @return StorageConfiguration The storage configuration instance.
         */
        public static function getStorageConfiguration(): StorageConfiguration
        {
            if(self::$storageConfiguration === null)
            {
                self::initializeConfiguration();
            }

            return self::$storageConfiguration;
        }
    }