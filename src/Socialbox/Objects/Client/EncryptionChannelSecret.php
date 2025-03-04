<?php

    namespace Socialbox\Objects\Client;

    use Socialbox\Classes\Cryptography;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\PeerAddress;

    class EncryptionChannelSecret implements SerializableInterface
    {
        private string $channelUuid;
        private PeerAddress $recipient;
        private string $localPublicEncryptionKey;
        private string $localPrivateEncryptionKey;
        private ?string $receivingPublicEncryptionKey;

        /**
         * Public constructor
         *
         * @param array $data The data to create the object
         */
        public function __construct(array $data)
        {
            $this->channelUuid = $data['channel_uuid'];
            $this->recipient = PeerAddress::fromAddress($data['recipient']);
            $this->localPublicEncryptionKey = $data['local_public_encryption_key'];
            $this->localPrivateEncryptionKey = $data['local_private_encryption_key'];
            $this->receivingPublicEncryptionKey = $data['receiving_public_encryption_key'] ?? null;
        }

        /**
         * Returns the channel UUID
         *
         * @return string
         */
        public function getChannelUuid(): string
        {
            return $this->channelUuid;
        }

        /**
         * Returns the receiver
         *
         * @return PeerAddress
         */
        public function getRecipient(): PeerAddress
        {
            return $this->recipient;
        }

        /**
         * Returns the calling public encryption key
         *
         * @return string
         */
        public function getLocalPublicEncryptionKey(): string
        {
            return $this->localPublicEncryptionKey;
        }

        /**
         * Returns the calling private encryption key
         *
         * @return string
         */
        public function getLocalPrivateEncryptionKey(): string
        {
            return $this->localPrivateEncryptionKey;
        }

        /**
         * Returns the receiving public encryption key
         *
         * @return string|null
         */
        public function getReceivingPublicEncryptionKey(): ?string
        {
            return $this->receivingPublicEncryptionKey;
        }

        /**
         * Sets the receiving public encryption key
         *
         * @param string $receivingPublicEncryptionKey The receiving public encryption key
         */
        public function setReceivingPublicEncryptionKey(string $receivingPublicEncryptionKey): void
        {
            $this->receivingPublicEncryptionKey = $receivingPublicEncryptionKey;
        }

        /**
         * Preform a Diffie-Hellman Exchange to get the shared secret between the two peers
         *
         * @return string The shared secret
         * @throws CryptographyException If the receiving public encryption key is not set
         */
        public function getSharedSecret(): string
        {
            if($this->receivingPublicEncryptionKey === null)
            {
                throw new CryptographyException('The receiving public encryption key is not set');
            }

            return Cryptography::performDHE($this->receivingPublicEncryptionKey, $this->localPrivateEncryptionKey);
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
                'channel_uuid' => $this->channelUuid,
                'recipient' => $this->recipient->getAddress(),
                'local_public_encryption_key' => $this->localPublicEncryptionKey,
                'local_private_encryption_key' => $this->localPrivateEncryptionKey,
                'receiving_public_encryption_key' => $this->receivingPublicEncryptionKey
            ];
        }
    }