<?php

    namespace Socialbox\Objects\Standard;

    use DateTime;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\RegisteredPeerManager;
    use Socialbox\Objects\Database\SessionRecord;

    class SessionState implements SerializableInterface
    {
        private string $uuid;
        private string $identifiedAs;
        private bool $authenticated;
        /**
         * @var SessionFlags[]|null
         */
        private ?array $flags;
        private int $created;
        private int $expires;

        /**
         * Constructor for initializing the object with the provided data.
         *
         * @param array $data An associative array containing the values for initializing the object.
         *                    - 'uuid': string, Unique identifier.
         *                    - 'identified_as': mixed, The identity information.
         *                    - 'authenticated': bool, Whether the object is authenticated.
         *                    - 'flags': string|null, Optional flags in
         */
        public function __construct(array $data)
        {
            $this->uuid = $data['uuid'];
            $this->identifiedAs = $data['identified_as'];
            $this->authenticated = $data['authenticated'];

            if(is_string($data['flags']))
            {
                $this->flags = SessionFlags::fromString($data['flags']);
            }
            elseif(is_array($data['flags']))
            {
                $this->flags = $data['flags'];
            }
            else
            {
                $this->flags = null;
            }

            if(is_int($data['created']))
            {
                $this->created = $data['created'];
            }
            elseif($data['created'] instanceof DateTime)
            {
                $this->created = $data['created']->getTimestamp();
            }
            else
            {
                $this->created = time();
            }

            if(is_int($data['expires']))
            {
                $this->expires = $data['expires'];
            }
            elseif($data['expires'] instanceof DateTime)
            {
                $this->expires = $data['expires']->getTimestamp();
            }
            else
            {
                $this->expires = time();
            }
        }

        /**
         * Retrieves the UUID of the current instance.
         *
         * @return string The UUID as a string.
         */
        public function getUuid(): string
        {
            return $this->uuid;
        }

        /**
         * Retrieves the identifier of the current instance.
         *
         * @return string The identifier as a string.
         */
        public function getIdentifiedAs(): string
        {
            return $this->identifiedAs;
        }

        /**
         * Checks if the user is authenticated.
         *
         * @return bool Returns true if the user is authenticated, otherwise false.
         */
        public function isAuthenticated(): bool
        {
            return $this->authenticated;
        }

        /**
         * Retrieves the flags associated with the current instance.
         *
         * @return array|null An array of flags or null if no flags are set.
         */
        public function getFlags(): ?array
        {
            return $this->flags;
        }

        /**
         * Checks if the provided flag exists within the current flags.
         *
         * @param string|SessionFlags $flag The flag to check, either as a string or an instance of SessionFlags.
         * @return bool Returns true if the flag is found in the current flags, otherwise false.
         */
        public function containsFlag(string|SessionFlags $flag): bool
        {
            if($this->flags === null || count($this->flags) === 0)
            {
                return false;
            }

            if($flag instanceof SessionFlags)
            {
                $flag = $flag->value;
            }

            return in_array($flag, $this->flags);
        }

        /**
         * Retrieves the creation timestamp of the current instance.
         *
         * @return int The creation timestamp as an integer.
         */
        public function getCreated(): int
        {
            return $this->created;
        }

        /**
         * Retrieves the expiration timestamp of the current instance.
         *
         * @return int The expiration timestamp as an integer.
         */
        public function getExpires(): int
        {
            return $this->expires;
        }

        /**
         * Creates a new instance of SessionState from the provided array.
         *
         * @param array $data The input array containing data to initialize the SessionState instance.
         * @return SessionState A new instance of the SessionState class.
         */
        public static function fromArray(array $data): SessionState
        {
            return new self($data);
        }

        /**
         * @inheritDoc
         */
        public static function fromSessionRecord(SessionRecord $sessionRecord): SessionState
        {
            return new self([
                'uuid' => $sessionRecord->getUuid(),
                'identified_as' => RegisteredPeerManager::getPeer($sessionRecord->getPeerUuid())->getAddress(),
                'authenticated' => $sessionRecord->isAuthenticated(),
                'flags' => $sessionRecord->getFlags(true),
                'created' => $sessionRecord->getCreated()->getTimestamp(),
                'expires' => $sessionRecord->getExpires()->getTimestamp()
            ]);
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'uuid' => $this->uuid,
                'identified_as' => $this->identifiedAs,
                'authenticated' => $this->authenticated,
                'flags' => $this->flags,
                'created' => $this->created,
                'expires' => $this->expires
            ];
        }
    }