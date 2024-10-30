<?php

namespace Socialbox\Objects\Database;

use DateTime;
use Socialbox\Enums\SessionState;
use Socialbox\Interfaces\SerializableInterface;

class SessionRecord implements SerializableInterface
{
    private string $uuid;
    private ?string $peerUuid;
    private bool $authenticated;
    private string $publicKey;
    private SessionState $state;
    private DateTime $created;
    private ?DateTime $lastRequest;

    public function __construct(array $data)
    {
        $this->uuid = $data['uuid'];
        $this->peerUuid = $data['peer_uuid'] ?? null;
        $this->authenticated = $data['authenticated'] ?? false;
        $this->publicKey = $data['public_key'];
        $this->created = $data['created'];
        $this->lastRequest = $data['last_request'];

        if(SessionState::tryFrom($data['state']) == null)
        {
            $this->state = SessionState::CLOSED;
        }
        else
        {
            $this->state = SessionState::from($data['state']);
        }
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getPeerUuid(): ?string
    {
        return $this->peerUuid;
    }

    public function isAuthenticated(): bool
    {
        if($this->peerUuid === null)
        {
            return false;
        }

        return $this->authenticated;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getState(): SessionState
    {
        return $this->state;
    }

    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function getLastRequest(): ?DateTime
    {
        return $this->lastRequest;
    }

    public static function fromArray(array $data): object
    {
        return new self($data);
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'peer_uuid' => $this->peerUuid,
            'authenticated' => $this->authenticated,
            'public_key' => $this->publicKey,
            'state' => $this->state->value,
            'created' => $this->created,
            'last_request' => $this->lastRequest,
        ];
    }
}