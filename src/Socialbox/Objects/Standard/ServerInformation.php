<?php

    namespace Socialbox\Objects\Standard;

    use Socialbox\Interfaces\SerializableInterface;

    class ServerInformation implements SerializableInterface
    {
        private string $serverName;
        private int $serverKeypairExpires;
        private string $transportEncryptionAlgorithm;

        /**
         * Constructor method to initialize the object with provided data.
         *
         * @param array $data The array containing initialization parameters, including 'server_name', 'server_keypair_expires', and 'transport_encryption_algorithm'.
         * @return void
         */
        public function __construct(array $data)
        {
            $this->serverName = $data['server_name'];
            $this->serverKeypairExpires = $data['server_keypair_expires'];
            $this->transportEncryptionAlgorithm = $data['transport_encryption_algorithm'];
        }

        /**
         * Retrieves the name of the server.
         *
         * @return string The server name.
         */
        public function getServerName(): string
        {
            return $this->serverName;
        }

        /**
         * Retrieves the expiration time of the server key pair.
         *
         * @return int The expiration timestamp of the server key pair.
         */
        public function getServerKeypairExpires(): int
        {
            return $this->serverKeypairExpires;
        }

        /**
         * Retrieves the transport encryption algorithm being used.
         *
         * @return string The transport encryption algorithm.
         */
        public function getTransportEncryptionAlgorithm(): string
        {
            return $this->transportEncryptionAlgorithm;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): ServerInformation
        {
            return new self($data);
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'server_name' => $this->serverName,
                'server_keypair_expires' => $this->serverKeypairExpires,
                'transport_encryption_algorithm' => $this->transportEncryptionAlgorithm,
            ];
        }
    }