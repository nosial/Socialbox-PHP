<?php

    namespace Socialbox\Classes\Configuration;

    class CacheConfiguration
    {
        private bool $enabled;
        private string $host;
        private int $port;
        private ?string $username;
        private ?string $password;
        private ?int $database;

        private bool $sessionsEnabled;
        private int $sessionsTtl;
        private int $sessionsMax;

        /**
         * Constructor to initialize configuration values.
         *
         * @param array $data An associative array containing configuration data.
         *                    Keys include:
         *                      - enabled (bool): Whether the feature is enabled.
         *                      - engine (string): The engine type.
         *                      - host (string): The host address.
         *                      - port (int): The port number.
         *                      - username (string|null): The username for authentication.
         *                      - password (string|null): The password for authentication.
         *                      - database (int|null): The database ID.
         *                      - sessions (array): Session-specific settings. Keys include:
         *                          - enabled (bool): Whether sessions are enabled.
         *                          - ttl (int): Session time-to-live in seconds.
         *                          - max (int): Maximum number of concurrent sessions.
         *
         * @return void
         */
        public function __construct(array $data)
        {
            $this->enabled = (bool)$data['enabled'];
            $this->host = (string)$data['host'];
            $this->port = (int)$data['port'];
            $this->username = $data['username'] ? (string)$data['username'] : null;
            $this->password = $data['password'] ? (string)$data['password'] : null;
            $this->database = $data['database'] ? (int)$data['database'] : null;
            $this->sessionsEnabled = (bool)$data['sessions']['enabled'];
            $this->sessionsTtl = (int)$data['sessions']['ttl'];
            $this->sessionsMax = (int)$data['sessions']['max'];
        }

        /**
         * Checks whether the feature is enabled.
         *
         * @return bool Returns true if the feature is enabled, false otherwise.
         */
        public function isEnabled(): bool
        {
            return $this->enabled;
        }
        
        /**
         * Retrieves the host value.
         *
         * @return string The host as a string.
         */
        public function getHost(): string
        {
            return $this->host;
        }

        /**
         * Retrieves the port value.
         *
         * @return int The port number.
         */
        public function getPort(): int
        {
            return $this->port;
        }

        /**
         * Retrieves the username value.
         *
         * @return string|null The username, or null if not set.
         */
        public function getUsername(): ?string
        {
            return $this->username;
        }

        /**
         * Retrieves the password value.
         *
         * @return string|null The password as a string or null if not set.
         */
        public function getPassword(): ?string
        {
            return $this->password;
        }

        /**
         * Retrieves the database identifier.
         *
         * @return int|null The database identifier or null if not set.
         */
        public function getDatabase(): ?int
        {
            return $this->database;
        }

        /**
         * Checks whether sessions are enabled.
         *
         * @return bool Returns true if sessions are enabled, otherwise false.
         */
        public function isSessionsEnabled(): bool
        {
            return $this->sessionsEnabled;
        }

        /**
         * Retrieves the time-to-live (TTL) value for sessions.
         *
         * @return int The TTL value for sessions.
         */
        public function getSessionsTtl(): int
        {
            return $this->sessionsTtl;
        }

        /**
         * Retrieves the maximum number of sessions allowed.
         *
         * @return int Returns the maximum number of sessions.
         */
        public function getSessionsMax(): int
        {
            return $this->sessionsMax;
        }
    }