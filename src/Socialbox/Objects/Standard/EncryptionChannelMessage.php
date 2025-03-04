<?php

    namespace Socialbox\Objects\Standard;

    use Socialbox\Enums\Status\EncryptionChannelStatus;
    use Socialbox\Interfaces\SerializableInterface;

    class EncryptionChannelMessage implements SerializableInterface
    {
        private string $messageUuid;
        private string $channelUuid;
        private EncryptionChannelStatus $status;
        private string $hash;
        private string $data;
        private int $timestamp;

        public function __construct(array $data)
        {
            $this->messageUuid = $data['message_uuid'];
            $this->channelUuid = $data['channel_uuid'];
            $this->status = EncryptionChannelStatus::from($data['status']);
            $this->hash = $data['hash'];
            $this->data = $data['data'];
            $this->timestamp = $data['timestamp'];
        }

        public function getMessageUuid(): string
        {
            return $this->messageUuid;
        }

        public function getChannelUuid(): string
        {
            return $this->channelUuid;
        }

        public function getStatus(): EncryptionChannelStatus
        {
            return $this->status;
        }

        public function getHash(): string
        {
            return $this->hash;
        }

        public function getData(): string
        {
            return $this->data;
        }

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
                'message_uuid' => $this->messageUuid,
                'channel_uuid' => $this->channelUuid,
                'status' => $this->status->value,
                'hash' => $this->hash,
                'data' => $this->data,
                'timestamp' => $this->timestamp
            ];
        }
    }