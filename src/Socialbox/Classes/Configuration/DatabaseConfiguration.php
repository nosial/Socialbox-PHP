<?php

    namespace Socialbox\Classes\Configuration;

    class DatabaseConfiguration
    {
        private string $host;
        private int $port;
        private string $username;
        private ?string $password;
        private string $name;

        /**
         * Constructor method to initialize properties from the provided data array.
         *
         * @param array $data Associative array containing the keys 'host', 'port', 'username', 'password', and 'name'.
         *                    - 'host' (string): The host of the server.
         *                    - 'port' (int): The port number.
         *                    - 'username' (string): The username for authentication.
         *                    - 'password' (string|null): The password for authentication, optional.
         *                    - 'name' (string): The name associated with the connection or resource.
         *
         * @return void
         */
        public function __construct(array $data)
        {
            $this->host = (string)$data['host'];
            $this->port = (int)$data['port'];
            $this->username = (string)$data['username'];
            $this->password = $data['password'] ? (string)$data['password'] : null;
            $this->name = (string)$data['name'];
        }

        /**
         * Retrieves the host value.
         *
         * @return string The value of the host.
         */
        public function getHost(): string
        {
            return $this->host;
        }

        /**
         * Retrieves the port value.
         *
         * @return int The value of the port.
         */
        public function getPort(): int
        {
            return $this->port;
        }

        /**
         * Retrieves the username value.
         *
         * @return string The value of the username.
         */
        public function getUsername(): string
        {
            return $this->username;
        }

        /**
         * Retrieves the password value.
         *
         * @return string|null The value of the password, or null if not set.
         */
        public function getPassword(): ?string
        {
            return $this->password;
        }

        /**
         * Retrieves the name value.
         *
         * @return string The value of the name
         */
        public function getName(): string
        {
            return $this->name;
        }


        /**
         * Constructs and retrieves the Data Source Name (DSN) string.
         *
         * @return string The DSN string for the database connection.
         */
        public function getDsn(): string
        {
            return sprintf('mysql:host=%s;dbname=%s;port=%s;charset=utf8mb4', $this->host, $this->name, $this->port);
        }
    }