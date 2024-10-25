<?php

namespace Socialbox\Objects\Database;

use DateTime;
use Socialbox\Enums\Flags\PeerFlags;
use Socialbox\Interfaces\SerializableInterface;

class RegisteredPeerRecord implements SerializableInterface
{
    private string $uuid;
    private string $username;
    private ?string $displayName;
    /**
     * @var PeerFlags[]
     */
    private ?array $flags;
    private bool $enabled;
    private DateTime $created;

    /**
     * Constructor for initializing class properties from provided data.
     *
     * @param array $data Array containing initialization data.
     * @return void
     */
    public function __construct(array $data)
    {
        $this->uuid = $data['uuid'];
        $this->username = $data['username'];
        $this->displayName = $data['display_name'] ?? null;

        if($data['flags'])
        {
            if(is_array($data['flags']))
            {
                $this->flags = array_map(fn($flag) => PeerFlags::from($flag), $data['flags']);
            }
            elseif(is_string($data['flags']))
            {
                $flags = explode(',', $data['flags']);
                $this->flags = array_map(fn($flag) => PeerFlags::from($flag), $flags);
            }
        }
        else
        {
            $this->flags = [];
        }

        $this->enabled = $data['enabled'];

        if (is_string($data['created']))
        {
            $this->created = new DateTime($data['created']);
        }
        else
        {
            $this->created = $data['created'];
        }
    }

    /**
     * Retrieves the UUID of the current instance.
     *
     * @return string The UUID.
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * Retrieves the username.
     *
     * @return string The username.
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Retrieves the display name.
     *
     * @return string|null The display name if set, or null otherwise.
     */
    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function getFlags(): array
    {
        return $this->flags;
    }

    public function flagExists(PeerFlags $flag): bool
    {
        return in_array($flag, $this->flags, true);
    }

    /**
     * Checks if the current instance is enabled.
     *
     * @return bool True if enabled, false otherwise.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Retrieves the creation date and time.
     *
     * @return DateTime The creation date and time.
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @inheritDoc
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
            'uuid' => $this->uuid,
            'username' => $this->username,
            'display_name' => $this->displayName,
            'flags' => implode(',', array_map(fn($flag) => $flag->name, $this->flags)),
            'enabled' => $this->enabled,
            'created' => $this->created
        ];
    }
}