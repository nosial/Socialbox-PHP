<?php

namespace Socialbox\Objects\Database;

use DateTime;
use Socialbox\Classes\Configuration;
use Socialbox\Enums\Status\CaptchaStatus;
use Socialbox\Interfaces\SerializableInterface;

class CaptchaRecord implements SerializableInterface
{
    private string $uuid;
    private string $peerUuid;
    private CaptchaStatus $status;
    private ?string $answer;
    private ?DateTime $answered;
    private DateTime $created;

    public function __construct(array $data)
    {
        $this->uuid = (string)$data['uuid'];
        $this->peerUuid = (string)$data['peer_uuid'];
        $this->status = CaptchaStatus::tryFrom((string)$data['status']);
        $this->answer = isset($data['answer']) ? (string)$data['answer'] : null;
        $this->answered = isset($data['answered']) ? new DateTime((string)$data['answered']) : null;
        $this->created = new DateTime((string)$data['created']);
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getPeerUuid(): string
    {
        return $this->peerUuid;
    }

    public function getStatus(): CaptchaStatus
    {
        return $this->status;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function getAnswered(): ?DateTime
    {
        return $this->answered;
    }

    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function getExpires(): DateTime
    {
        return $this->created->modify(sprintf("+%s seconds", Configuration::getSecurityConfiguration()->getCaptchaTtl()));
    }

    public function isExpired(): bool
    {
        return $this->getExpires() < new DateTime();
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data): object
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
            'peer_uuid' => $this->peerUuid,
            'status' => $this->status->value,
            'answer' => $this->answer,
            'answered' => $this->answered?->format('Y-m-d H:i:s'),
            'created' => $this->created->format('Y-m-d H:i:s')
        ];
    }
}