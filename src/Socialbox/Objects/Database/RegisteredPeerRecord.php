<?php

    namespace Socialbox\Objects\Database;

    use DateTime;
    use Socialbox\Classes\Configuration;
    use Socialbox\Enums\Flags\PeerFlags;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\Standard\SelfUser;

    class RegisteredPeerRecord implements SerializableInterface
    {
        private string $uuid;
        private string $username;
        private string $server;
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
         * @throws \DateMalformedStringException
         */
        public function __construct(array $data)
        {
            $this->uuid = $data['uuid'];
            $this->username = $data['username'];
            $this->server = $data['server'];
            $this->displayName = $data['display_name'] ?? null;

            if($data['flags'])
            {
                $this->flags = PeerFlags::fromString($data['flags']);
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
         * Retrieves the server.
         *
         * @return string The server.
         */
        public function getServer(): string
        {
            return $this->server;
        }

        /**
         * Constructs and retrieves the peer address using the current instance's username and the domain from the configuration.
         *
         * @return string The constructed peer address.
         */
        public function getAddress(): string
        {
            return sprintf("%s@%s", $this->username, Configuration::getInstanceConfiguration()->getDomain());
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

        public function removeFlag(PeerFlags $flag): void
        {
            $key = array_search($flag, $this->flags, true);
            if($key !== false)
            {
                unset($this->flags[$key]);
            }
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
         * Determines if the server is external.
         *
         * @return bool True if the server is external, false otherwise.
         */
        public function isExternal(): bool
        {
            return $this->server === 'host';
        }

        /**
         * Converts the current instance to a SelfUser object.
         *
         * @return SelfUser The SelfUser object.
         */
        public function toSelfUser(): SelfUser
        {
            return new SelfUser($this);
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
                'server' => $this->server,
                'display_name' => $this->displayName,
                'flags' => PeerFlags::toString($this->flags),
                'enabled' => $this->enabled,
                'created' => $this->created
            ];
        }
    }