<?php

    namespace Socialbox\Objects\Database;

    use DateTime;
    use InvalidArgumentException;
    use Socialbox\Enums\Status\EncryptionChannelState;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\Standard\EncryptionChannel;

    class EncryptionChannelRecord implements SerializableInterface
    {
        private string $uuid;
        private PeerAddress $callingPeer;
        private string $callingSignatureUuid;
        private string $callingEncryptionPublicKey;
        private PeerAddress $receivingPeer;
        private ?string $receivingSignatureUuid;
        private ?string $receivingSignaturePublicKey;
        private ?string $receivingEncryptionPublicKey;
        private string $transportEncryptionAlgorithm;
        private ?string $transportEncryptionKey;
        private EncryptionChannelState $state;
        private DateTime $created;

        /**
         * Public Constructor for the encryption channel record
         *
         * @param array $data
         * @throws \DateMalformedStringException
         */
        public function __construct(array $data)
        {
            $this->uuid = $data['uuid'];

            if(!isset($data['calling_peer']))
            {
                throw new InvalidArgumentException('Missing property calling_peer');
            }
            else
            {
                if(is_string($data['calling_peer']))
                {
                    $this->callingPeer = PeerAddress::fromAddress($data['calling_peer']);
                }
                elseif($data['calling_peer'] instanceof PeerAddress)
                {
                    $this->callingPeer = $data['calling_peer'];
                }
                else
                {
                    throw new InvalidArgumentException('Unexpected calling_peer type, got ' . gettype($data['calling_peer']));
                }
            }

            $this->callingSignatureUuid = $data['calling_signature_uuid'];
            $this->callingEncryptionPublicKey = $data['calling_encryption_public_key'];

            if(!isset($data['receiving_peer']))
            {
                throw new InvalidArgumentException('Missing property receiving_peer');
            }
            else
            {
                if(is_string($data['receiving_peer']))
                {
                    $this->receivingPeer = PeerAddress::fromAddress($data['receiving_peer']);
                }
                elseif($data['receiving_peer'] instanceof PeerAddress)
                {
                    $this->receivingPeer = $data['receiving_peer'];
                }
                else
                {
                    throw new InvalidArgumentException('Unexpected receiving_peer type, got ' . gettype($data['receiving_peer']));
                }
            }

            $this->receivingSignatureUuid = $data['receiving_signature_uuid'] ?? null;
            $this->receivingSignaturePublicKey = $data['receiving_signature_public_key'] ?? null;
            $this->receivingEncryptionPublicKey = $data['receiving_encryption_public_key'] ?? null;
            $this->transportEncryptionAlgorithm = $data['transport_encryption_algorithm'];
            $this->transportEncryptionKey = $data['transport_encryption_key'] ?? null;
            $this->state = EncryptionChannelState::tryFrom($data['state']) ?? EncryptionChannelState::ERROR;

            if(!isset($data['created']))
            {
                throw new InvalidArgumentException('Missing property created');
            }
            else
            {
                if(is_string($data['created']))
                {
                    $this->created = new DateTime($data['created']);
                }
                elseif(is_int($data['created']))
                {
                    $this->created = (new DateTime())->setTimestamp($data['created']);
                }
                elseif($data['created'] instanceof DateTime)
                {
                    $this->created = $data['created'];
                }
                else
                {
                    throw new InvalidArgumentException('Unexpected created type, got ' . gettype($data['created']));
                }
            }
        }

        /**
         * Returns the Unique Universal Identifier for the encryption record
         *
         * @return string
         */
        public function getUuid(): string
        {
            return $this->uuid;
        }

        /**
         * Returns the address of the calling peer
         *
         * @return PeerAddress
         */
        public function getCallingPeer(): PeerAddress
        {
            return $this->callingPeer;
        }

        /**
         * Returns the UUID of the signing keypair that the caller is using
         *
         * @return string
         */
        public function getCallingSignatureUuid(): string
        {
            return $this->callingSignatureUuid;
        }

        /**
         * Returns the public key of the encryption keypair that the caller is using
         *
         * @return string
         */
        public function getCallingEncryptionPublicKey(): string
        {
            return $this->callingEncryptionPublicKey;
        }

        /**
         * Returns the address of the receiving peer
         *
         * @return PeerAddress
         */
        public function getReceivingPeer(): PeerAddress
        {
            return $this->receivingPeer;
        }

        /**
         * Returns the UUID of the signing keypair that the receiver is using
         *
         * @return string|null
         */
        public function getReceivingSignatureUuid(): ?string
        {
            return $this->receivingSignatureUuid;
        }

        /**
         * Returns the public key of the signing keypair that the receiver is using
         *
         * @return string|null
         */
        public function getReceivingSignaturePublicKey(): ?string
        {
            return $this->receivingSignaturePublicKey;
        }

        /**
         * Returns the public key of the encryption keypair that the receiver is using
         *
         * @return string|null
         */
        public function getReceivingEncryptionPublicKey(): ?string
        {
            return $this->receivingEncryptionPublicKey;
        }

        /**
         * Returns the algorithm used for transport encryption
         *
         * @return string
         */
        public function getTransportEncryptionAlgorithm(): string
        {
            return $this->transportEncryptionAlgorithm;
        }

        /**
         * Returns the key used for transport encryption
         *
         * @return string|null
         */
        public function getTransportEncryptionKey(): ?string
        {
            return $this->transportEncryptionKey;
        }

        /**
         * Returns the current state of the encryption channel
         *
         * @return EncryptionChannelState
         */
        public function getState(): EncryptionChannelState
        {
            return $this->state;
        }

        /**
         * Returns the creation date of the encryption channel
         *
         * @return DateTime
         */
        public function getCreated(): DateTime
        {
            return $this->created;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): EncryptionChannelRecord
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
                'calling_peer' => $this->callingPeer->getAddress(),
                'calling_signature_uuid' => $this->callingSignatureUuid,
                'calling_encryption_public_key' => $this->callingEncryptionPublicKey,
                'receiving_peer' => $this->receivingPeer->getAddress(),
                'receiving_signature_uuid' => $this->receivingSignatureUuid,
                'receiving_signature_public_key' => $this->receivingSignaturePublicKey,
                'receiving_encryption_public_key' => $this->receivingEncryptionPublicKey,
                'transport_encryption_algorithm' => $this->transportEncryptionAlgorithm,
                'transport_encryption_key' => $this->transportEncryptionKey,
                'state' => $this->state->value,
                'created' => $this->created->format('Y-m-d H:i:s')
            ];
        }

        /**
         * Converts the Encryption Channel Record to a Standard Encryption Channel
         *
         * @return EncryptionChannel
         */
        public function toStandard(): EncryptionChannel
        {
            return new EncryptionChannel($this->toArray());
        }
    }