<?php

namespace Socialbox\Classes\Configuration;

class DatabaseConfiguration
{
    private string $host;
    private int $port;
    private string $username;
    private ?string $password;
    private string $name;

    public function __construct(array $data)
    {
        $this->host = (string)$data['host'];
        $this->port = (int)$data['port'];
        $this->username = (string)$data['username'];
        $this->password = $data['password'] ? (string)$data['password'] : null;
        $this->name = (string)$data['name'];
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getName(): string
    {
        return $this->name;
    }
}