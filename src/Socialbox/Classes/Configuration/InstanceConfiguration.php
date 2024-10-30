<?php


namespace Socialbox\Classes\Configuration;

class InstanceConfiguration
{
    private bool $enabled;
    private ?string $domain;
    private ?string $rpcEndpoint;
    private ?string $privateKey;
    private ?string $publicKey;

    /**
     * Constructor that initializes object properties with the provided data.
     *
     * @param array $data An associative array with keys 'enabled', 'domain', 'private_key', and 'public_key'.
     * @return void
     */
    public function __construct(array $data)
    {
        $this->enabled = (bool)$data['enabled'];
        $this->domain = $data['domain'];
        $this->rpcEndpoint = $data['rpc_endpoint'];
        $this->privateKey = $data['private_key'];
        $this->publicKey = $data['public_key'];
    }

    /**
     * Checks if the current object is enabled.
     *
     * @return bool True if the object is enabled, false otherwise.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Retrieves the domain.
     *
     * @return string|null The domain.
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * @return string|null
     */
    public function getRpcEndpoint(): ?string
    {
        return $this->rpcEndpoint;
    }

    /**
     * Retrieves the private key.
     *
     * @return string|null The private key.
     */
    public function getPrivateKey(): ?string
    {
        return $this->privateKey;
    }

    /**
     * Retrieves the public key.
     *
     * @return string|null The public key.
     */
    public function getPublicKey(): ?string
    {
        return $this->publicKey;
    }
}