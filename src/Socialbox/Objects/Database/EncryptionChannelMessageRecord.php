<?php

    namespace Socialbox\Objects\Database;

    use DateMalformedStringException;
    use DateTime;
    use InvalidArgumentException;
    use Socialbox\Enums\Status\EncryptionChannelMessageStatus;
    use Socialbox\Enums\Types\EncryptionMessageRecipient;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\Standard\EncryptionChannelMessage;

    class EncryptionChannelMessageRecord implements SerializableInterface
    {
        private string $uuid;
        private string $channelUuid;
        private EncryptionMessageRecipient $recipient;
        private EncryptionChannelMessageStatus $status;
        private string $hash;
        private string $data;
        private DateTime $timestamp;

        /**
         * Constructs the Channel
         *
         * @param array $data
         */
        public function __construct(array $data)
        {
            $this->uuid = $data['uuid'];
            $this->channelUuid = $data['channel_uuid'];
            $this->recipient = EncryptionMessageRecipient::from($data['recipient']);
            $this->status = EncryptionChannelMessageStatus::from($data['status']);
            $this->hash = $data['hash'];
            $this->data = $data['data'];

            if($data['timestamp'] instanceof DateTime)
            {
                $this->timestamp = $data['timestamp'];
            }
            elseif(is_int($data['timestamp']))
            {
                $this->timestamp = (new DateTime())->setTimestamp($data['timestamp']);
            }
            elseif(is_string($data['timestamp']))
            {
                try
                {
                    $this->timestamp = new DateTime($data['timestamp']);
                }
                catch (DateMalformedStringException $e)
                {
                    throw new InvalidArgumentException('Invalid DateTime format for timestamp, got: ' . $data['timestamp'], $e->getCode(), $e);
                }
            }
            else
            {
                throw new InvalidArgumentException('Invalid timestamp type, got: ' . gettype($data['timestamp']));
            }
        }

        /**
         * Returns the Unique Universal Identifier for the message
         *
         * @return string The Message's Unique Universal Identifier
         */
        public function getUuid(): string
        {
            return $this->uuid;
        }

        /**
         * Returns the Unique Universal Identifier of the channel that this message belongs to
         *
         * @return string The Channel's Unique Universal Identifier
         */
        public function getChannelUuid(): string
        {
            return $this->channelUuid;
        }

        /**
         * Returns the recipient of the message
         *
         * @return EncryptionMessageRecipient The recipient of the message
         */
        public function getRecipient(): EncryptionMessageRecipient
        {
            return $this->recipient;
        }

        /**
         * Returns the status of the message
         *
         * @return EncryptionChannelMessageStatus The status of the message
         */
        public function getStatus(): EncryptionChannelMessageStatus
        {
            return $this->status;
        }

        /**
         * Returns the SHA512 hash of the decrypted content
         *
         * @return string The SHA512 hash of the decrypted content
         */
        public function getHash(): string
        {
            return $this->hash;
        }

        /**
         * Returns the encrypted content of the message
         *
         * @return string The encrypted content of the message
         */
        public function getData(): string
        {
            return $this->data;
        }

        /**
         * Returns the Timestamp for when this message was created
         *
         * @return DateTime The Timestamp for when the message was created
         */
        public function getTimestamp(): DateTime
        {
            return $this->timestamp;
        }

        /**
         * Returns the owner of the message
         *
         * @param EncryptionChannelRecord $channelRecord The channel record to use
         * @return PeerAddress The owner of the message
         */
        public function getOwner(EncryptionChannelRecord $channelRecord): PeerAddress
        {
            if($this->recipient === EncryptionMessageRecipient::SENDER)
            {
                return $channelRecord->getCallingPeerAddress();
            }

            return $channelRecord->getReceivingPeerAddress();
        }

        /**
         * Returns the receiver of the message
         *
         * @param EncryptionChannelRecord $channelRecord The channel record to use
         * @return PeerAddress The receiver of the message
         */
        public function getReceiver(EncryptionChannelRecord $channelRecord): PeerAddress
        {
            if($this->recipient === EncryptionMessageRecipient::SENDER)
            {
                return $channelRecord->getReceivingPeerAddress();
            }

            return $channelRecord->getCallingPeerAddress();
        }

        /**
         * Converts the record to a standard message
         *
         * @return EncryptionChannelMessage The standard message
         */
        public function toStandard(): EncryptionChannelMessage
        {
            return new EncryptionChannelMessage([
                'message_uuid' => $this->uuid,
                'channel_uuid' => $this->channelUuid,
                'status' => $this->status->value,
                'hash' => $this->hash,
                'data' => $this->data,
                'timestamp' => $this->timestamp->getTimestamp()
            ]);
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): EncryptionChannelMessageRecord
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
                'channel_uuid' => $this->channelUuid,
                'recipient' => $this->recipient->value,
                'status' => $this->status->value,
                'hash' => $this->hash,
                'data' => $this->data,
                'timestamp' => $this->timestamp->getTimestamp()
            ];
        }
    }