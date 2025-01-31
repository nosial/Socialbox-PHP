<?php

    namespace Socialbox\Objects\Standard;

    use DateTime;
    use Socialbox\Enums\Flags\PeerFlags;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\Database\PeerDatabaseRecord;

    class SelfUser implements SerializableInterface
    {
        private string $address;
        private string $username;
        /**
         * @var PeerFlags[]
         */
        private array $flags;
        /**
         * @var InformationFieldState[]
         */
        private array $informationFieldStates;
        private bool $passwordEnabled;
        private ?int $passwordLastUpdated;
        private bool $otpEnabled;
        private ?int $otpLastUpdated;
        private int $registered;

        /**
         * Constructor for initializing the object with provided data.
         *
         * @param array $data Data array containing initial values for object properties.
         */
        public function __construct(array $data)
        {
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
                $this->registered = $data['created']->getTimestamp();
            }
            else
            {
                $this->registered = $data['created'];
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
        public function getRegistered(): int
        {
            return $this->registered;
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
                'created' => $this->registered
            ];
        }
    }