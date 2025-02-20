<?php

    namespace Socialbox\Objects\Database;

    use DateMalformedStringException;
    use DateTime;
    use InvalidArgumentException;
    use Socialbox\Enums\Types\CommunicationRecipientType;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\Standard\EncryptionChannelMessage;

    class ChannelMessageRecord implements SerializableInterface
    {
        private string $uuid;
        private string $channelUuid;
        private CommunicationRecipientType $recipient;
        private string $message;
        private string $signature;
        private bool $received;
        private DateTime $timestamp;

        /**
         * Constructs a new instance of this class and initializes its properties with the provided data.
         *
         * @param array $data An associative array containing initialization data. Expected keys:
         *                    - 'uuid' (string): The unique identifier.
         *                    - 'channel_uuid' (string): The channel UUID.
         *                    - 'recipient' (string): The recipient type, which will be cast to a CommunicationRecipientType instance.
         *                    - 'message' (string): The message.
         *                    - 'signature' (string): The signature.
         *                    - 'received' (bool): Whether the message has been received.
         *                    - 'timestamp' (int|string|\DateTime): The timestamp of the message.
         */
        public function __construct(array $data)
        {
            $this->uuid = $data['uuid'];
            $this->channelUuid = $data['channel_uuid'];
            $this->recipient = CommunicationRecipientType::from($data['recipient']);
            $this->message = $data['message'];
            $this->signature = $data['signature'];
            $this->received = (bool)$data['received'];

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
                $this->timestamp = new DateTime($data['timestamp']);
            }
            else
            {
                throw new InvalidArgumentException('Invalid timestamp type, got ' . gettype($data['timestamp']));
            }
        }

        /**
         * Returns the unique identifier for the message.
         *
         * @return string
         */
        public function getUuid(): string
        {
            return $this->uuid;
        }

        /**
         * Returns the UUID of the channel that the message belongs to.
         *
         * @return string
         */
        public function getChannelUuid(): string
        {
            return $this->channelUuid;
        }

        /**
         * Returns the recipient type of the message.
         *
         * @return CommunicationRecipientType
         */
        public function getRecipient(): CommunicationRecipientType
        {
            return $this->recipient;
        }

        /**
         * Returns the message content.
         *
         * @return string
         */
        public function getMessage(): string
        {
            return $this->message;
        }

        /**
         * Returns the signature of the message.
         *
         * @return string
         */
        public function getSignature(): string
        {
            return $this->signature;
        }

        /**
         * Returns whether the message has been received.
         *
         * @return bool
         */
        public function isReceived(): bool
        {
            return $this->received;
        }

        /**
         * Returns the timestamp of the message.
         *
         * @return DateTime
         */
        public function getTimestamp(): DateTime
        {
            return $this->timestamp;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): ChannelMessageRecord
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
                'message' => $this->message,
                'signature' => $this->signature,
                'received' => $this->received,
                'timestamp' => $this->timestamp->format('Y-m-d H:i:s')
            ];
        }


        public function toStandard(): EncryptionChannelMessage
        {
            return new EncryptionChannelMessage($this->toArray());
        }
    }