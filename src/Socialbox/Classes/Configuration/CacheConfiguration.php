<?php

namespace Socialbox\Classes\Configuration;

class CacheConfiguration
{
    private bool $enabled;
    private string $engine;
    private string $host;
    private int $port;
    private ?string $username;
    private ?string $password;
    private ?int $database;

    private bool $sessionsEnabled;
    private int $sessionsTtl;
    private int $sessionsMax;

    public function __construct(array $data)
    {
        $this->enabled = (bool)$data['enabled'];
        $this->engine = (string)$data['engine'];
        $this->host = (string)$data['host'];
        $this->port = (int)$data['port'];
        $this->username = $data['username'] ? (string)$data['username'] : null;
        $this->password = $data['password'] ? (string)$data['password'] : null;
        $this->database = $data['database'] ? (int)$data['database'] : null;

        $this->sessionsEnabled = (bool)$data['sessions.enabled'];
        $this->sessionsTtl = (int)$data['sessions.ttl'];
        $this->sessionsMax = (int)$data['sessions.max'];
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getEngine(): string
    {
        return $this->engine;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getDatabase(): ?int
    {
        return $this->database;
    }

    public function isSessionsEnabled(): bool
    {
        return $this->sessionsEnabled;
    }

    public function getSessionsTtl(): int
    {
        return $this->sessionsTtl;
    }

    public function getSessionsMax(): int
    {
        return $this->sessionsMax;
    }
}