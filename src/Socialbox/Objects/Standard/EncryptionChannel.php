<?php

    namespace Socialbox\Objects\Standard;

    use DateTime;
    use InvalidArgumentException;
    use Socialbox\Enums\Status\EncryptionChannelState;
    use Socialbox\Interfaces\SerializableInterface;

    class EncryptionChannel implements SerializableInterface
    {
        private string $uuid;
        private string $callingPeer;
        private string $callingSignatureUuid;
        private string $callingSignaturePublicKey;
        private string $callingEncryptionPublicKey;
        private string $receivingPeer;
        private ?string $receivingSignatureUuid;
        private ?string $receivingSignaturePublicKey;
        private ?string $receivingEncryptionPublicKey;
        private string $transportEncryptionAlgorithm;
        private ?string $transportEncryptionKey;
        private EncryptionChannelState $state;
        private int $created;

        /**
         * EncryptionChannel constructor.
         *
         * @param array $data
         */
        public function __construct(array $data)
        {
            $this->uuid = $data['uuid'];
            $this->callingPeer = $data['calling_peer'];
            $this->callingSignatureUuid = $data['calling_signature_uuid'];
            $this->callingSignaturePublicKey = $data['calling_signature_public_key'];
            $this->callingEncryptionPublicKey = $data['calling_encryption_public_key'];
            $this->receivingPeer = $data['receiving_peer'];
            $this->receivingSignatureUuid = $data['receiving_signature_uuid'];
            $this->receivingSignaturePublicKey = $data['receiving_signature_public_key'];
            $this->receivingEncryptionPublicKey = $data['receiving_encryption_public_key'];
            $this->transportEncryptionAlgorithm = $data['transport_encryption_algorithm'];
            $this->transportEncryptionKey = $data['transport_encryption_key'];
            $this->state = EncryptionChannelState::from($data['state']);

            if($data['created'] instanceof DateTime)
            {
                $this->created = $data['created']->getTimestamp();
            }
            elseif(is_int($data['created']))
            {
                $this->created = $data['created'];
            }
            elseif(is_string($data['created']))
            {
                $this->created = strtotime($data['created']) ?: throw new InvalidArgumentException('Invalid date format');
            }
            else
            {
                throw new InvalidArgumentException('Invalid date format, got type: ' . gettype($data['created']));
            }
        }

        /**
         * Returns the Unique Universal Identifier of the Encryption Channel
         *
         * @return string The UUID of the Encryption Channel
         */
        public function getUuid(): string
        {
            return $this->uuid;
        }

        /**
         * Returns the Peer address that initiated the Encryption Channel
         *
         * @return string The Peer address that initiated the Encryption Channel
         */
        public function getCallingPeer(): string
        {
            return $this->callingPeer;
        }

        /**
         * Returns the Unique Universal Identifier of the Signature used by the calling Peer
         *
         * @return string The UUID of the Signature used by the calling Peer
         */
        public function getCallingSignatureUuid(): string
        {
            return $this->callingSignatureUuid;
        }

        /**
         * Returns the Public Key of the Signature used by the calling Peer
         *
         * @return string The Public Key of the Signature used by the calling Peer
         */
        public function getCallingSignaturePublicKey(): string
        {
            return $this->callingSignaturePublicKey;
        }

        /**
         * Returns the Public Key of the Encryption used by the calling Peer
         *
         * @return string The Public Key of the Encryption used by the calling Peer
         */
        public function getCallingEncryptionPublicKey(): string
        {
            return $this->callingEncryptionPublicKey;
        }

        /**
         * Returns the Peer address that received the Encryption Channel
         *
         * @return string The Peer address that received the Encryption Channel
         */
        public function getReceivingPeer(): string
        {
            return $this->receivingPeer;
        }

        /**
         * Returns the Unique Universal Identifier of the Signature used by the receiving Peer
         *
         * @return string|null The UUID of the Signature used by the receiving Peer, or null if not set
         */
        public function getReceivingSignatureUuid(): ?string
        {
            return $this->receivingSignatureUuid;
        }

        /**
         * Returns the Public Key of the Signature used by the receiving Peer
         *
         * @return string|null The Public Key of the Signature used by the receiving Peer, or null if not set
         */
        public function getReceivingSignaturePublicKey(): ?string
        {
            return $this->receivingSignaturePublicKey;
        }

        /**
         * Returns the Public Key of the Encryption used by the receiving Peer
         *
         * @return string|null The Public Key of the Encryption used by the receiving Peer, or null if not set
         */
        public function getReceivingEncryptionPublicKey(): ?string
        {
            return $this->receivingEncryptionPublicKey;
        }

        /**
         * Returns the Algorithm used for the Transport Encryption
         *
         * @return string The Algorithm used for the Transport Encryption
         */
        public function getTransportEncryptionAlgorithm(): string
        {
            return $this->transportEncryptionAlgorithm;
        }

        /**
         * Returns the Key used for the Transport Encryption
         *
         * @return string|null The Key used for the Transport Encryption, or null if not set
         */
        public function getTransportEncryptionKey(): ?string
        {
            return $this->transportEncryptionKey;
        }

        /**
         * Returns the State of the Encryption Channel
         *
         * @return EncryptionChannelState The State of the Encryption Channel
         */
        public function getState(): EncryptionChannelState
        {
            return $this->state;
        }

        /**
         * Returns the Unix Timestamp of the creation date of the Encryption Channel
         *
         * @return int The Unix Timestamp of the creation date of the Encryption Channel
         */
        public function getCreated(): int
        {
            return $this->created;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): EncryptionChannel
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
                'calling_peer' => $this->callingPeer,
                'calling_signature_uuid' => $this->callingSignatureUuid,
                'calling_encryption_public_key' => $this->callingEncryptionPublicKey,
                'receiving_peer' => $this->receivingPeer,
                'receiving_signature_uuid' => $this->receivingSignatureUuid,
                'receiving_signature_public_key' => $this->receivingSignaturePublicKey,
                'receiving_encryption_public_key' => $this->receivingEncryptionPublicKey,
                'transport_encryption_algorithm' => $this->transportEncryptionAlgorithm,
                'transport_encryption_key' => $this->transportEncryptionKey,
                'state' => $this->state->value,
                'created' => $this->created
            ];
        }
    }