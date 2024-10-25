<?php

namespace Socialbox\Objects\Database;

use DateTime;
use Socialbox\Interfaces\SerializableInterface;
use Socialbox\Objects\ResolvedServer;

class ResolvedServerRecord implements SerializableInterface
{
    private string $domain;
    private string $endpoint;
    private string $publicKey;
    private DateTime $updated;

    /**
     * Constructs a new instance of the class.
     *
     * @param array $data An associative array containing the domain, endpoint, public_key, and updated values.
     * @throws \DateMalformedStringException
     */
    public function __construct(array $data)
    {
        $this->domain = (string)$data['domain'];
        $this->endpoint = (string)$data['endpoint'];
        $this->publicKey = (string)$data['public_key'];

        if(is_null($data['updated']))
        {
            $this->updated = new DateTime();
        }
        elseif (is_string($data['updated']))
        {
            $this->updated = new DateTime($data['updated']);
        }
        else
        {
            $this->updated = $data['updated'];
        }
    }

    /**
     *
     * @return string The domain value.
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     *
     * @return string The endpoint value.
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     *
     * @return string The public key.
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Retrieves the timestamp of the last update.
     *
     * @return DateTime The DateTime object representing the last update time.
     */
    public function getUpdated(): DateTime
    {
        return $this->updated;
    }

    /**
     * Converts the record to a ResolvedServer object.
     *
     * @return ResolvedServer The ResolvedServer object.
     */
    public function toResolvedServer(): ResolvedServer
    {
        return new ResolvedServer($this->endpoint, $this->publicKey);
    }

    /**
     * @inheritDoc
     * @throws \DateMalformedStringException
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
            'domain' => $this->domain,
            'endpoint' => $this->endpoint,
            'public_key' => $this->publicKey,
            'updated' => $this->updated->format('Y-m-d H:i:s')
        ];
    }
}