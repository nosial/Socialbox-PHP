<?php

namespace Socialbox\Objects;

use Socialbox\Enums\StandardHeaders;

class ClientRequest
{
    /**
     * @var array
     */
    private array $headers;

    /**
     * @var RpcRequest[]
     */
    private array $requests;

    /**
     * @var string
     */
    private string $requestHash;

    /**
     * ClientRequest constructor.
     *
     * @param array $headers The headers of the request
     * @param RpcRequest[] $requests The RPC requests of the client
     */
    public function __construct(array $headers, array $requests, string $requestHash)
    {
        $this->headers = $headers;
        $this->requests = $requests;
        $this->requestHash = $requestHash;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return RpcRequest[]
     */
    public function getRequests(): array
    {
        return $this->requests;
    }

    public function getHash(): string
    {
        return $this->requestHash;
    }

    public function getClientName(): string
    {
        return $this->headers[StandardHeaders::CLIENT_NAME->value];
    }

    public function getClientVersion(): string
    {
        return $this->headers[StandardHeaders::CLIENT_VERSION->value];
    }

    public function getSessionUuid(): ?string
    {
        if(!isset($this->headers[StandardHeaders::SESSION_UUID->value]))
        {
            return null;
        }

        return $this->headers[StandardHeaders::SESSION_UUID->value];
    }

    public function getFromPeer(): ?PeerAddress
    {
        if(!isset($this->headers[StandardHeaders::FROM_PEER->value]))
        {
            return null;
        }

        return PeerAddress::fromAddress($this->headers[StandardHeaders::FROM_PEER->value]);
    }

    public function getSignature(): ?string
    {
        if(!isset($this->headers[StandardHeaders::SIGNATURE->value]))
        {
            return null;
        }

        return $this->headers[StandardHeaders::SIGNATURE->value];
    }

    public function verifySignature(): bool
    {
        $signature = $this->getSignature();

        if($signature == null)
        {
            return false;
        }


    }
}