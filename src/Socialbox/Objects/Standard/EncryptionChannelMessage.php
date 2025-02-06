<?php

    namespace Socialbox\Objects\Standard;

    use DateTime;
    use InvalidArgumentException;
    use Socialbox\Enums\Types\CommunicationRecipientType;
    use Socialbox\Interfaces\SerializableInterface;

    class EncryptionChannelMessage implements SerializableInterface
    {
        private string $uuid;
        private string $channelUuid;
        private CommunicationRecipientType $recipient;
        private string $message;
        private string $signature;
        private bool $received;
        private int $timestamp;

        /**
         * EncryptionChannelMessage constructor.
         *
         * @param array $data
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
                $this->timestamp = $data['timestamp']->getTimestamp();
            }
            elseif(is_int($data['timestamp']))
            {
                $this->timestamp = $data['timestamp'];
            }
            elseif(is_string($data['timestamp']))
            {
                $this->timestamp = strtotime($data['timestamp']) ?: throw new InvalidArgumentException('Invalid date format');
            }
            else
            {
                throw new InvalidArgumentException('Invalid date format, got type: ' . gettype($data['timestamp']));
            }
        }

        /**
         * The Unique Universal Identifier of the message.
         *
         * @return string The UUID of the message.
         */
        public function getUuid(): string
        {
            return $this->uuid;
        }

        /**
         * The Unique Universal Identifier of the channel.
         *
         * @return string The UUID of the channel.
         */
        public function getChannelUuid(): string
        {
            return $this->channelUuid;
        }

        /**
         * The recipient of the message.
         *
         * @return CommunicationRecipientType The recipient of the message.
         */
        public function getRecipient(): CommunicationRecipientType
        {
            return $this->recipient;
        }

        /**
         * The encrypted message.
         *
         * @return string The message.
         */
        public function getMessage(): string
        {
            return $this->message;
        }

        /**
         * The signature of the decrypted message.
         *
         * @return string The signature of the message.
         */
        public function getSignature(): string
        {
            return $this->signature;
        }

        /**
         * Whether the message has been received.
         *
         * @return bool Whether the message has been received.
         */
        public function isReceived(): bool
        {
            return $this->received;
        }

        /**
         * The timestamp of the message.
         *
         * @return int The timestamp of the message.
         */
        public function getTimestamp(): int
        {
            return $this->timestamp;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): EncryptionChannelMessage
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
                'timestamp' => $this->timestamp
            ];
        }
    }