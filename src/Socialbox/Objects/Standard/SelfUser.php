<?php

namespace Socialbox\Objects\Standard;

use DateTime;
use Socialbox\Enums\Flags\PeerFlags;
use Socialbox\Interfaces\SerializableInterface;
use Socialbox\Objects\Database\RegisteredPeerRecord;

class SelfUser implements SerializableInterface
{
    private string $uuid;
    private bool $enabled;
    private string $address;
    private string $username;
    private ?string $displayName;
    /**
     * @var PeerFlags[]
     */
    private array $flags;
    private int $created;

    /**
     * Constructor for initializing the object with provided data.
     *
     * @param array|RegisteredPeerRecord $data Data array containing initial values for object properties.
     */
    public function __construct(array|RegisteredPeerRecord $data)
    {
        if($data instanceof RegisteredPeerRecord)
        {
            $this->uuid = $data->getUuid();
            $this->enabled = $data->isEnabled();
            $this->username = $data->getUsername();
            $this->address = $data->getAddress();
            $this->displayName = $data->getDisplayName();
            $this->flags = $data->getFlags();
            $this->created = $data->getCreated()->getTimestamp();

            return;
        }

        $this->uuid = $data['uuid'];
        $this->enabled = $data['enabled'];
        $this->username = $data['username'];
        $this->address = $data['address'];
        $this->displayName = $data['display_name'] ?? null;

        if(is_string($data['flags']))
        {
            $this->flags = PeerFlags::fromString($data['flags']);
        }
        elseif(is_array($data['flags']))
        {
            $this->flags = $data['flags'];
        }
        else
        {
            $this->flags = [];
        }

        if($data['created'] instanceof DateTime)
        {
            $this->created = $data['created']->getTimestamp();
        }
        else
        {
            $this->created = $data['created'];
        }

        return;
    }

    /**
     * Retrieves the UUID of the object.
     *
     * @return string The UUID of the object.
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     *
     * @return string The username of the user.
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     *
     * @return string|null The display name.
     */
    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    /**
     *
     * @return array
     */
    public function getFlags(): array
    {
        return $this->flags;
    }

    /**
     *
     * @return int The timestamp when the object was created.
     */
    public function getCreated(): int
    {
        return $this->created;
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data): SelfUser
    {
        return new self($data);
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $flags = [];
        foreach($this->flags as $flag)
        {
            $flags[] = $flag->value;
        }

        return [
            'uuid' => $this->uuid,
            'enabled' => $this->enabled,
            'username' => $this->username,
            'address' => $this->address,
            'display_name' => $this->displayName,
            'flags' => $flags,
            'created' => $this->created
        ];
    }
}