<?php

    namespace Socialbox\Objects\Standard;

    use DateTime;
    use InvalidArgumentException;
    use Socialbox\Interfaces\SerializableInterface;

    class KnownSigningKey implements SerializableInterface
    {
        private string $uuid;
        private string $name;
        private string $publicKey;
        private int $expires;
        private int $created;
        private int $trustedOn;

        /**
         * Constructs a new instance of the class, initializing its properties using the provided data.
         *
         * @param array $data An associative array containing initialization data.
         *                    Expected keys are:
         *                    - 'uuid' (string): The unique identifier for the instance.
         *                    - 'name' (string|null): The optional name for the instance.
         *                    - 'public_key' (string): The public key associated with the instance.
         *                    - 'expires' (int|DateTime): The expiration timestamp or DateTime object.
         *                    - 'created' (int|DateTime): The creation timestamp or DateTime object.
         *                    - 'trusted_on' (int|DateTime): The trusted on timestamp or DateTime object.
         *
         * @return void
         * @throws InvalidArgumentException If 'expires' or 'created' are not valid integer timestamps or DateTime instances.
         */
        public function __construct(array $data)
        {
            $this->uuid = $data['uuid'];
            $this->name = $data['name'];
            $this->publicKey = $data['public_key'];

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
                throw new InvalidArgumentException('Invalid expires value');
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
                throw new InvalidArgumentException('Invalid created value');
            }

            if(is_int($data['trusted_on']))
            {
                $this->trustedOn = $data['trusted_on'];
            }
            elseif($data['trusted_on'] instanceof DateTime)
            {
                $this->trustedOn = $data['trusted_on']->getTimestamp();
            }
            else
            {
                throw new InvalidArgumentException('Invalid trusted_on value');
            }
        }

        /**
         * Retrieves the UUID associated with this instance.
         *
         * @return string The UUID as a string.
         */
        public function getUuid(): string
        {
            return $this->uuid;
        }

        /**
         * Retrieves the name associated with this instance.
         *
         * @return string|null The name as a string, or null if not set.
         */
        public function getName(): ?string
        {
            return $this->name;
        }

        /**
         *
         * Retrieves the public key.
         *
         * @return string The public key.
         */
        public function getPublicKey(): string
        {
            return $this->publicKey;
        }

        /**
         * Retrieves the expiration time.
         *
         * @return int The expiration time as an integer.
         */
        public function getExpires(): int
        {
            return $this->expires;
        }

        /**
         *
         * @return int The timestamp representing the creation time.
         */
        public function getCreated(): int
        {
            return $this->created;
        }

        /**
         * Retrieves the timestamp representing the time the key was trusted.
         *
         * @return int The timestamp representing the time the key was trusted.
         */
        public function getTrustedOn(): int
        {
            return $this->trustedOn;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): KnownSigningKey
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
                'name' => $this->name,
                'public_key' => $this->publicKey,
                'expires' => $this->expires,
                'created' => $this->created,
                'trusted_on' => $this->trustedOn
            ];
        }
    }