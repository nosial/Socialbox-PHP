<?php

    namespace Socialbox\Objects\Database;

    use DateTime;
    use InvalidArgumentException;
    use Socialbox\Classes\Configuration;
    use Socialbox\Enums\Flags\PeerFlags;
    use Socialbox\Enums\ReservedUsernames;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\Standard\InformationFieldState;
    use Socialbox\Objects\Standard\Peer;

    class PeerDatabaseRecord implements SerializableInterface
    {
        private string $uuid;
        private string $username;
        private string $server;
        /**
         * @var PeerFlags[]
         */
        private ?array $flags;
        private bool $enabled;
        private DateTime $created;
        private DateTime $updated;

        /**
         * Constructor for initializing class properties from provided data.
         *
         * @param array $data Array containing initialization data.
         */
        public function __construct(array $data)
        {
            $this->uuid = $data['uuid'];
            $this->username = $data['username'];

            if($data['server'] === ReservedUsernames::HOST->value)
            {
                $this->server = Configuration::getInstanceConfiguration()->getDomain();
            }
            else
            {
                $this->server = $data['server'];
            }

            if($data['flags'])
            {
                $this->flags = PeerFlags::fromString($data['flags']);
            }
            else
            {
                $this->flags = [];
            }

            $this->enabled = $data['enabled'];

            if(!isset($data['created']))
            {
                $this->created = new DateTime();
            }
            elseif(is_int($data['created']))
            {
                $this->created = (new DateTime())->setTimestamp($data['created']);
            }
            elseif(is_string($data['created']))
            {
                try
                {
                    $this->created = new DateTime($data['created']);
                }
                catch (\DateMalformedStringException $e)
                {
                    throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
                }
            }
            else
            {
                throw new InvalidArgumentException("The created field must be a valid timestamp or date string.");
            }

            if(!isset($data['updated']))
            {
                $this->updated = new DateTime();
            }
            elseif(is_int($data['updated']))
            {
                $this->updated = (new DateTime())->setTimestamp($data['updated']);
            }
            elseif(is_string($data['updated']))
            {
                try
                {
                    $this->updated = new DateTime($data['updated']);
                }
                catch (\DateMalformedStringException $e)
                {
                    throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
                }
            }
            else
            {
                throw new InvalidArgumentException("The updated field must be a valid timestamp or date string.");
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
         * Retrieves the flags.
         *
         * @return PeerFlags[] The flags.
         */
        public function getFlags(): array
        {
            return $this->flags;
        }

        /**
         * Adds a flag to the current instance.
         *
         * @param PeerFlags $flag The flag to add.
         */
        public function flagExists(PeerFlags $flag): bool
        {
            return in_array($flag, $this->flags, true);
        }

        /**
         * Adds a flag to the current instance.
         *
         * @param PeerFlags $flag The flag to add.
         */
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
         * Retrieves the last update date and time.
         *
         * @return DateTime The last update date and time.
         */
        public function getUpdated(): DateTime
        {
            return $this->updated;
        }

        /**
         * Determines if the user is considered external by checking if the username is 'host' and the server
         * is not the same as the domain from the configuration.
         *
         * @return bool True if the user is external, false otherwise.
         */
        public function isExternal(): bool
        {
            return $this->username === ReservedUsernames::HOST->value || $this->server !== Configuration::getInstanceConfiguration()->getDomain();
        }

        /**
         * Converts the current instance to a Peer object.
         *
         * @param PeerInformationFieldRecord[]|InformationFieldState[]|array $informationFields 
         * @return Peer The Peer representation of the current instance.
         */
        public function toStandardPeer(array $informationFields): Peer
        {
            return Peer::fromArray([
                'address' => $this->getAddress(),
                'information_fields' => $informationFields,
                'flags' => array_map(fn(PeerFlags $flag) => $flag->value, $this->flags),
                'registered' => $this->created->getTimestamp()
            ]);
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
                'flags' => PeerFlags::toString($this->flags),
                'enabled' => $this->enabled,
                'created' => $this->created,
                'updated' => $this->updated
            ];
        }
    }