<?php

    namespace Socialbox\Classes\Configuration;

    class InstanceConfiguration
    {
        private bool $enabled;
        private ?string $domain;
        private ?string $rpcEndpoint;
        private int $encryptionKeysCount;
        private int $encryptionRecordsCount;
        private ?string $privateKey;
        private ?string $publicKey;
        private ?array $encryptionKeys;

        /**
         * Constructor that initializes object properties with the provided data.
         *
         * @param array $data An associative array with keys 'enabled', 'domain', 'private_key', and 'public_key'.
         * @return void
         */
        public function __construct(array $data)
        {
            $this->enabled = (bool)$data['enabled'];
            $this->domain = $data['domain'];
            $this->rpcEndpoint = $data['rpc_endpoint'];
            $this->encryptionKeysCount = $data['encryption_keys_count'];
            $this->encryptionRecordsCount = $data['encryption_records_count'];
            $this->privateKey = $data['private_key'];
            $this->publicKey = $data['public_key'];
            $this->encryptionKeys = $data['encryption_keys'];
        }

        /**
         * Checks if the current object is enabled.
         *
         * @return bool True if the object is enabled, false otherwise.
         */
        public function isEnabled(): bool
        {
            return $this->enabled;
        }

        /**
         * Retrieves the domain.
         *
         * @return string|null The domain.
         */
        public function getDomain(): ?string
        {
            return $this->domain;
        }

        /**
         * @return string|null
         */
        public function getRpcEndpoint(): ?string
        {
            return $this->rpcEndpoint;
        }

        /**
         * Retrieves the number of encryption keys.
         *
         * @return int The number of encryption keys.
         */
        public function getEncryptionKeysCount(): int
        {
            return $this->encryptionKeysCount;
        }

        /**
         * Retrieves the number of encryption records.
         *
         * @return int The number of encryption records.
         */
        public function getEncryptionRecordsCount(): int
        {
            return $this->encryptionRecordsCount;
        }

        /**
         * Retrieves the private key.
         *
         * @return string|null The private key.
         */
        public function getPrivateKey(): ?string
        {
            return $this->privateKey;
        }

        /**
         * Retrieves the public key.
         *
         * @return string|null The public key.
         */
        public function getPublicKey(): ?string
        {
            return $this->publicKey;
        }

        /**
         * Retrieves the encryption keys.
         *
         * @return array|null The encryption keys.
         */
        public function getEncryptionKeys(): ?array
        {
            return $this->encryptionKeys;
        }

        /**
         * @return string
         */
        public function getRandomEncryptionKey(): string
        {
            return $this->encryptionKeys[array_rand($this->encryptionKeys)];
        }
    }