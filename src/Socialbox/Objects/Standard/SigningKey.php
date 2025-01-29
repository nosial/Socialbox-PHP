<?php

    namespace Socialbox\Objects\Standard;

    use DateTime;
    use InvalidArgumentException;
    use Socialbox\Enums\SigningKeyState;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\Database\SigningKeyRecord;

    class SigningKey implements SerializableInterface
    {
        private string $uuid;
        private ?string $name;
        private string $publicKey;
        private SigningKeyState $state;
        private int $expires;
        private int $created;

        /**
         * Constructs a new instance of the class, initializing its properties using the provided data.
         *
         * @param array $data An associative array containing initialization data.
         *                    Expected keys are:
         *                    - 'uuid' (string): The unique identifier for the instance.
         *                    - 'name' (string|null): The optional name for the instance.
         *                    - 'public_key' (string): The public key associated with the instance.
         *                    - 'state' (string): The state of the signing key.
         *                    - 'expires' (int|DateTime): The expiration timestamp or DateTime object.
         *                    - 'created' (int|DateTime): The creation timestamp or DateTime object.
         *
         * @return void
         * @throws InvalidArgumentException If 'expires' or 'created' are not valid integer timestamps or DateTime instances.
         */
        public function __construct(array $data)
        {
            $this->uuid = $data['uuid'];
            $this->name = $data['name'] ?? null;
            $this->publicKey = $data['public_key'];
            $this->state = SigningKeyState::from($data['state']);

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
         * Retrieves the current state of the signing key.
         *
         * @return SigningKeyState The current state of the signing key.
         */
        public function getState(): SigningKeyState
        {
            if($this->expires > 0 && time() > $this->expires)
            {
                return SigningKeyState::EXPIRED;
            }

            return $this->state;
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
         * Creates a new SigningKey instance from a SigningKeyRecord.
         *
         * @param SigningKeyRecord $record The record containing the signing key data.
         * @return SigningKey An instance of SigningKey populated with data from the provided record.
         */
        public static function fromSigningKeyRecord(SigningKeyRecord $record): SigningKey
        {
            return new self([
                'uuid' => $record->getUuid(),
                'name' => $record->getName(),
                'public_key' => $record->getPublicKey(),
                'state' => $record->getState()->value,
                'expires' => $record->getExpires(),
                'created' => $record->getCreated()
            ]);
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): SigningKey
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
                'state' => $this->getState()->value,
                'expires' => $this->expires,
                'created' => $this->created
            ];
        }
    }