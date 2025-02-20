<?php

    namespace Socialbox\Objects\Client;

    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\PeerAddress;

    class EncryptionChannelSecret implements SerializableInterface
    {
        private string $channelUuid;
        private PeerAddress $receiver;
        private string $signatureUuid;
        private string $publicEncryptionKey;
        private string $privateEncryptionKey;
        private string $transportEncryptionAlgorithm;
        private ?string $transportEncryptionKey;

        /**
         * Public constructor
         *
         * @param array $data The data to create the object
         */
        public function __construct(array $data)
        {
            $this->channelUuid = $data['uuid'];
            $this->receiver = PeerAddress::fromAddress($data['receiver']);
            $this->signatureUuid = $data['signature_uuid'];
            $this->publicEncryptionKey = $data['public_encryption_key'];
            $this->privateEncryptionKey = $data['private_encryption_key'];
            $this->transportEncryptionAlgorithm = $data['transport_encryption_algorithm'];
            $this->transportEncryptionKey = $data['transport_encryption_key'] ?? null;
        }

        /**
         * Returns the UUID of the key pair
         *
         * @return string The UUID of the key pair
         */
        public function getChannelUuid(): string
        {
            return $this->channelUuid;
        }

        /**
         * @return PeerAddress
         */
        public function getReceiver(): PeerAddress
        {
            return $this->receiver;
        }

        /**
         * Returns the UUID of the signature
         *
         * @return string The UUID of the signature
         */
        public function getSignatureUuid(): string
        {
            return $this->signatureUuid;
        }
        
        /**
         * Returns the public key of the key pair
         *
         * @return string The public key of the key pair
         */
        public function getPublicEncryptionKey(): string
        {
            return $this->publicEncryptionKey;
        }

        /**
         * Returns the private key of the key pair
         *
         * @return string The private key of the key pair
         */
        public function getPrivateEncryptionKey(): string
        {
            return $this->privateEncryptionKey;
        }

        /**
         * @return string
         */
        public function getTransportEncryptionAlgorithm(): string
        {
            return $this->transportEncryptionAlgorithm;
        }

        /**
         * @return string|null
         */
        public function getTransportEncryptionKey(): ?string
        {
            return $this->transportEncryptionKey;
        }

        /**
         * @param string|null $transportEncryptionKey
         */
        public function setTransportEncryptionKey(?string $transportEncryptionKey): void
        {
            $this->transportEncryptionKey = $transportEncryptionKey;
        }
        
        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): EncryptionChannelSecret
        {
            return new self($data);
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'uuid' => $this->channelUuid,
                'receiver' => $this->receiver->getAddress(),
                'signature_uuid' => $this->signatureUuid,
                'public_key' => $this->publicEncryptionKey,
                'private_key' => $this->privateEncryptionKey,
                'transport_encryption_algorithm' => $this->transportEncryptionAlgorithm,
                'transport_encryption_key' => $this->transportEncryptionKey
            ];
        }
    }