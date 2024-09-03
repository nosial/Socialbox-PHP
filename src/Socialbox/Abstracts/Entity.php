<?php

namespace Socialbox\Abstracts;

use Socialbox\Enums\EntityType;
use Socialbox\Interfaces\SerializableInterface;

abstract class Entity implements SerializableInterface
{
    protected string $uuid;
    protected EntityType $type;
    protected string $username;
    protected string $domain;
    protected string $display_name;

    /**
     * Constructs the entity object from an array of data.
     *
     * @param array $data The data to construct the entity from.
     */
    public function __construct(array $data)
    {
        $this->uuid = $data['uuid'];
        $this->type = $data['type'];
        $this->username = $data['username'];
        $this->domain = $data['domain'];
        $this->display_name = $data['display_name'];
    }

    /**
     * Returns the Unique Universal Identifier (UUID v4) of the entity.
     *
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * Returns the EntityType of the entity.
     *
     * @return EntityType
     */
    public function getType(): EntityType
    {
        return $this->type;
    }

    /**
     * Returns the username of the entity.
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Returns the domain that the entity belongs to, `LOCAL` for local entities.
     *
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * Returns the display name of the entity.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->display_name;
    }

    /**
     * Returns the address of the entity in the format `username@domain`.
     *
     * @return string
     */
    public function getAddress(): string
    {
        return sprintf('%s@%s', $this->username, $this->domain);
    }

    /**
     * Serializes the entity to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type,
            'username' => $this->username,
            'domain' => $this->domain,
            'display_name' => $this->display_name,
        ];
    }

    /**
     * Constructs the entity object from an array of data.
     *
     * @param array $data The data to construct the entity from.
     */
    public static function fromArray(array $data): static
    {
        return new static($data);
    }
}