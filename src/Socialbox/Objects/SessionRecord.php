<?php

namespace Socialbox\Objects;

use DateTime;
use Socialbox\Enums\SessionState;
use Socialbox\Interfaces\SerializableInterface;

class SessionRecord implements SerializableInterface
{
    private string $uuid;
    private ?string $authenticatedPeerUuid;
    private string $publicKey;
    private SessionState $state;
    private DateTime $created;
    private DateTime $lastRequest;

    public function __construct(array $data)
    {
        $this->uuid = $data['uuid'];
        $this->authenticatedPeerUuid = $data['authenticated_peer_uuid'] ?? null;
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

    public function getAuthenticatedPeerUuid(): ?string
    {
        return $this->authenticatedPeerUuid;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getState(): SessionState
    {
        return $this->state;
    }

    public function getCreated(): int
    {
        return $this->created;
    }

    public function getLastRequest(): int
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
            'authenticated_peer_uuid' => $this->authenticatedPeerUuid,
            'public_key' => $this->publicKey,
            'state' => $this->state->value,
            'created' => $this->created,
            'last_request' => $this->lastRequest,
        ];
    }
}