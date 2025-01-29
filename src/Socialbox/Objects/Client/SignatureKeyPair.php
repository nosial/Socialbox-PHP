<?php

    namespace Socialbox\Objects\Client;

    use Socialbox\Interfaces\SerializableInterface;

    class SignatureKeyPair implements SerializableInterface
    {
        private string $uuid;
        private ?string $name;
        private string $publicKey;
        private string $privateKey;
        private ?int $expires;

        /**
         * Public constructor
         *
         * @param array $data The data to create the object
         */
        public function __construct(array $data)
        {
            $this->uuid = $data['uuid'];
            $this->name = $data['name'] ?? null;
            $this->publicKey = $data['public_key'];
            $this->privateKey = $data['private_key'];
            $this->expires = (int)$data['expires'] ?? null;
        }

        /**
         * Returns the UUID of the key pair
         *
         * @return string The UUID of the key pair
         */
        public function getUuid(): string
        {
            return $this->uuid;
        }

        /**
         * Returns the name of the key pair
         *
         * @return string|null The name of the key pair
         */
        public function getName(): ?string
        {
            return $this->name;
        }

        /**
         * Returns the public key of the key pair
         *
         * @return string The public key of the key pair
         */
        public function getPublicKey(): string
        {
            return $this->publicKey;
        }

        /**
         * Returns the private key of the key pair
         *
         * @return string The private key of the key pair
         */
        public function getPrivateKey(): string
        {
            return $this->privateKey;
        }

        /**
         * Returns the expiration date of the key pair
         *
         * @return int|null The expiration date of the key pair
         */
        public function getExpires(): ?int
        {
            return $this->expires;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): SignatureKeyPair
        {
            return new self($data);
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'uuid' => $this->uuid,
                'name' => $this->name,
                'public_key' => $this->publicKey,
                'private_key' => $this->privateKey,
                'expires' => $this->expires
            ];
        }
    }