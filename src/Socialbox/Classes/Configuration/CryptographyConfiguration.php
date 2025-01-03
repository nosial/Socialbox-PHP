<?php

    namespace Socialbox\Classes\Configuration;

    class CryptographyConfiguration
    {
        private ?int $hostKeyPairExpires;
        private ?string $hostPublicKey;
        private ?string $hostPrivateKey;
        private ?array $internalEncryptionKeys;
        private int $encryptionKeysCount;
        private string $encryptionKeysAlgorithm;
        private string $transportEncryptionAlgorithm;

        /**
         * Constructor to initialize encryption and transport keys from provided data.
         *
         * @param array $data An associative array containing key-value pairs for encryption keys, algorithms, and expiration settings.
         * @return void
         */
        public function __construct(array $data)
        {
            $this->hostKeyPairExpires = $data['host_keypair_expires'] ?? null;
            $this->hostPublicKey = $data['host_public_key'] ?? null;
            $this->hostPrivateKey = $data['host_private_key'] ?? null;
            $this->internalEncryptionKeys = $data['internal_encryption_keys'] ?? null;
            $this->encryptionKeysCount = $data['encryption_keys_count'];
            $this->encryptionKeysAlgorithm = $data['encryption_keys_algorithm'];
            $this->transportEncryptionAlgorithm = $data['transport_encryption_algorithm'];
        }

        /**
         * Retrieves the expiration timestamp of the host key pair.
         *
         * @return int|null The expiration timestamp of the host key pair, or null if not set.
         */
        public function getHostKeyPairExpires(): ?int
        {
            return $this->hostKeyPairExpires;
        }

        /**
         * Retrieves the host's public key.
         *
         * @return string|null The host's public key, or null if not set.
         */
        public function getHostPublicKey(): ?string
        {
            return $this->hostPublicKey;
        }

        /**
         * Retrieves the private key associated with the host.
         *
         * @return string|null The host's private key, or null if not set.
         */
        public function getHostPrivateKey(): ?string
        {
            return $this->hostPrivateKey;
        }

        /**
         * Retrieves the internal encryption keys.
         *
         * @return array|null Returns an array of internal encryption keys if set, or null if no keys are available.
         */
        public function getInternalEncryptionKeys(): ?array
        {
            return $this->internalEncryptionKeys;
        }

        /**
         * Retrieves a random internal encryption key from the available set of encryption keys.
         *
         * @return string|null Returns a randomly selected encryption key as a string, or null if no keys are available.
         */
        public function getRandomInternalEncryptionKey(): ?string
        {
            return $this->internalEncryptionKeys[array_rand($this->internalEncryptionKeys)];
        }

        /**
         * Retrieves the total count of encryption keys.
         *
         * @return int The number of encryption keys.
         */
        public function getEncryptionKeysCount(): int
        {
            return $this->encryptionKeysCount;
        }

        /**
         * Retrieves the algorithm used for the encryption keys.
         *
         * @return string The encryption keys algorithm.
         */
        public function getEncryptionKeysAlgorithm(): string
        {
            return $this->encryptionKeysAlgorithm;
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
    }