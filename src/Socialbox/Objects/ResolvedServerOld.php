<?php

namespace Socialbox\Objects;

class ResolvedServer
{
    private string $endpoint;
    private string $publicKey;

    public function __construct(string $endpoint, string $publicKey)
    {
        $this->endpoint = $endpoint;
        $this->publicKey = $publicKey;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }
}