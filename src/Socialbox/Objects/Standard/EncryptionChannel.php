<?php

    namespace Socialbox\Objects\Standard;

    use DateMalformedStringException;
    use DateTime;
    use InvalidArgumentException;
    use Socialbox\Enums\Status\EncryptionChannelStatus;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\PeerAddress;

    class EncryptionChannel implements SerializableInterface
    {
        private string $uuid;
        private EncryptionChannelStatus $status;
        private PeerAddress $callingPeer;
        private string $callingPublicEncryptionKey;
        private PeerAddress $receivingPeer;
        private ?string $receivingPublicEncryptionKey;
        private int $created;

        /**
         * Public Constructor for the encryption channel
         *
         * @param array $data
         */
        public function __construct(array $data)
        {
            $this->uuid = $data['uuid'];
            $this->status = EncryptionChannelStatus::from($data['status']);
            $this->callingPeer = PeerAddress::fromAddress($data['calling_peer']);
            $this->callingPublicEncryptionKey = $data['calling_public_encryption_key'];
            $this->receivingPeer = PeerAddress::fromAddress($data['receiving_peer']);
            $this->receivingPublicEncryptionKey = $data['receiving_public_encryption_key'] ?? null;

            if(is_int($data['created']))
            {
                $this->created = $data['created'];
            }
            if(is_string($data['created']))
            {
                try
                {
                    $this->created = (new DateTime($data['created']))->getTimestamp();
                }
                catch (DateMalformedStringException $e)
                {
                    throw new InvalidArgumentException($e->getMessage(), $e->getCode());
                }
            }
            elseif($data['created'] instanceof DateTime)
            {
                $this->created = $data['created']->getTimestamp();
            }
            else
            {
                throw new InvalidArgumentException('Invalid created type, got: ' . gettype($data['created']));
            }
        }

        public function getUuid(): string
        {
            return $this->uuid;
        }

        public function getStatus(): EncryptionChannelStatus
        {
            return $this->status;
        }

        public function getCallingPeer(): PeerAddress
        {
            return $this->callingPeer;
        }

        public function getCallingPublicEncryptionKey(): string
        {
            return $this->callingPublicEncryptionKey;
        }

        public function getReceivingPeer(): PeerAddress
        {
            return $this->receivingPeer;
        }

        public function getReceivingPublicEncryptionKey(): ?string
        {
            return $this->receivingPublicEncryptionKey;
        }

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
                'status' => $this->status->value,
                'calling_peer' => $this->callingPeer->getAddress(),
                'calling_public_encryption_key' => $this->callingPublicEncryptionKey,
                'receiving_peer' => $this->receivingPeer->getAddress(),
                'receiving_public_encryption_key' => $this->receivingPublicEncryptionKey,
                'created' => $this->created
            ];
        }
    }