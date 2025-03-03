<?php

    namespace Socialbox\Objects\Database;

    use DateMalformedStringException;
    use DateTime;
    use InvalidArgumentException;
    use Socialbox\Enums\Status\EncryptionChannelStatus;
    use Socialbox\Enums\Types\EncryptionMessageRecipient;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\Standard\EncryptionChannel;
    use Socialbox\Objects\Standard\Peer;

    class EncryptionChannelRecord implements SerializableInterface
    {
        private string $uuid;
        private EncryptionChannelStatus $status;
        private PeerAddress $callingPeerAddress;
        private string $callingPublicEncryptionKey;
        private PeerAddress $receivingPeerAddress;
        private ?string $receivingPublicEncryptionKey;
        private DateTime $created;

        /**
         * Constructs the Encryption Channel Record from an array representation of the object, requires the following
         * fields:
         *  - uuid
         *  - status
         *  - calling_peer_address
         *  - calling_public_encryption_key
         *  - receiving_peer_address
         *  - created
         * The only optional field is `receiving_public_encryption_key`
         *
         * @param array $data
         */
        public function __construct(array $data)
        {
            $this->uuid = $data['uuid'];
            $this->status = EncryptionChannelStatus::from($data['status']);
            $this->callingPeerAddress = PeerAddress::fromAddress($data['calling_peer_address']);
            $this->callingPublicEncryptionKey = $data['calling_public_encryption_key'];
            $this->receivingPeerAddress = PeerAddress::fromAddress($data['receiving_peer_address']);
            $this->receivingPublicEncryptionKey = $data['receiving_public_encryption_key'] ?? null;

            if($data['created'] instanceof DateTime)
            {
                $this->created = $data['created'];
            }
            elseif(is_int($data['created']))
            {
                $this->created = (new DateTime())->setTimestamp($data['created']);
            }
            elseif(is_string($data['created']))
            {
                try
                {
                    $this->created = new DateTime($data['created']);
                }
                catch (DateMalformedStringException $e)
                {
                    throw new InvalidArgumentException('Invalid DateTime given in created, got: ' . $data['created'], $e->getCode(), $e);
                }
            }
            else
            {
                throw new InvalidArgumentException('Invalid created type, got: ' . gettype($data['created']));
            }
        }

        /**
         * Returns the Universal Unique Identifier of the encryption channel record
         *
         * @return string The UUID V4
         */
        public function getUuid(): string
        {
            return $this->uuid;
        }

        /**
         * Returns the current status of the encryption channel record
         *
         * @return EncryptionChannelStatus The current status of the encryption channel record
         */
        public function getStatus(): EncryptionChannelStatus
        {
            return $this->status;
        }

        /**
         * Returns the PeerAddress of the calling peer for the encryption channel record
         *
         * @return PeerAddress The address of the calling peer
         */
        public function getCallingPeerAddress(): PeerAddress
        {
            return $this->callingPeerAddress;
        }

        /**
         * Returns the public encryption key of the calling peer
         *
         * @return string The public encryption key of the caller
         */
        public function getCallingPublicEncryptionKey(): string
        {
            return $this->callingPublicEncryptionKey;
        }

        /**
         * Returns the PeerAddress of the receiving peer for the encryption channel record
         *
         * @return PeerAddress
         */
        public function getReceivingPeerAddress(): PeerAddress
        {
            return $this->receivingPeerAddress;
        }

        /**
         * Returns the public encryption key of the receiving peer
         *
         * @return string|null The public encryption key of the receiver
         */
        public function getReceivingPublicEncryptionKey(): ?string
        {
            return $this->receivingPublicEncryptionKey;
        }

        /**
         * The DateTime object of when the record was created
         *
         * @return DateTime The DateTime object of the record's creation date
         */
        public function getCreated(): DateTime
        {
            return $this->created;
        }

        /**
         * Checks if the given peer address is a participant in the encryption channel
         *
         * @param string|PeerAddress $peerAddress The peer address to check
         * @return bool True if the peer address is a participant in the encryption channel
         */
        public function isParticipant(string|PeerAddress $peerAddress): bool
        {
            if($peerAddress instanceof PeerAddress)
            {
                $peerAddress = $peerAddress->getAddress();
            }

            return $this->callingPeerAddress->getAddress() === $peerAddress || $this->receivingPeerAddress->getAddress() === $peerAddress;
        }

        /**
         * Returns the external peer address of the encryption channel
         *
         * @return PeerAddress|null The external peer address of the encryption channel, or null if there is none
         */
        public function getExternalPeer(): ?PeerAddress
        {
            if($this->callingPeerAddress->isExternal())
            {
                return $this->callingPeerAddress;
            }

            if($this->receivingPeerAddress->isExternal())
            {
                return $this->receivingPeerAddress;
            }

            return null;
        }

        /**
         * Determines the recipient of the encryption message based on the requester
         *
         * @param string|PeerAddress $requester The requester of the message
         * @return EncryptionMessageRecipient|null The recipient of the message, or null if the requester is not a participant
         */
        public function determineRecipient(string|PeerAddress $requester, bool $reverse=false): ?EncryptionMessageRecipient
        {
            if($requester instanceof PeerAddress)
            {
                $requester = $requester->getAddress();
            }

            if($reverse)
            {
                if($this->callingPeerAddress->getAddress() === $requester)
                {
                    return EncryptionMessageRecipient::SENDER;
                }
                elseif($this->receivingPeerAddress->getAddress() === $requester)
                {
                    return EncryptionMessageRecipient::RECEIVER;
                }

                return null;
            }

            if($this->callingPeerAddress->getAddress() === $requester)
            {
                return EncryptionMessageRecipient::RECEIVER;
            }
            elseif($this->receivingPeerAddress->getAddress() === $requester)
            {
                return EncryptionMessageRecipient::SENDER;
            }

            return null;
        }

        /**
         * Determines the receiver of the encryption message based on the sender
         *
         * @param string|PeerAddress $sender The sender of the message
         * @return PeerAddress|null The receiver of the message, or null if the sender is not a participant
         */
        public function  determineReceiver(string|PeerAddress $sender): ?PeerAddress
        {
            if($sender instanceof PeerAddress)
            {
                $sender = $sender->getAddress();
            }

            if($this->callingPeerAddress->getAddress() === $sender)
            {
                return $this->receivingPeerAddress;
            }
            elseif($this->receivingPeerAddress->getAddress() === $sender)
            {
                return $this->callingPeerAddress;
            }

            return null;
        }

        /**
         * Returns a standard representation object representation of this internal database object
         *
         * @return EncryptionChannel
         */
        public function toStandard(): EncryptionChannel
        {
            return new EncryptionChannel([
                'uuid' => $this->uuid,
                'status' => $this->status->value,
                'calling_peer' => $this->callingPeerAddress->getAddress(),
                'calling_public_encryption_key' => $this->callingPublicEncryptionKey,
                'receiving_peer' => $this->receivingPeerAddress->getAddress(),
                'receiving_public_encryption_key' => $this->receivingPublicEncryptionKey,
                'created' => $this->created
            ]);
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
                'status' => $this->status->value,
                'calling_peer_address' => $this->callingPeerAddress->getAddress(),
                'calling_public_encryption_key' => $this->callingPublicEncryptionKey,
                'receiving_peer_address' => $this->receivingPeerAddress->getAddress(),
                'created' => $this->created->getTimestamp()
            ];
        }
    }