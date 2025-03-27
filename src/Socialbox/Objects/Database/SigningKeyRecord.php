<?php

    namespace Socialbox\Objects\Database;

    use DateTime;
    use InvalidArgumentException;
    use Socialbox\Enums\SigningKeyState;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\Standard\Signature;

    class SigningKeyRecord implements SerializableInterface
    {
        private string $peerUuid;
        private string $uuid;
        private ?string $name;
        private string $publicKey;
        private SigningKeyState $state;
        private int $expires;
        private int $created;

        /**
         * Constructs a new instance of this class and initializes its properties with the provided data.
         *
         * @param array $data An associative array containing initialization data. Expected keys:
         *                    - 'peer_uuid' (string): The peer UUID.
         *                    - 'uuid' (string): The unique identifier.
         *                    - 'name' (string|null): The name, which is optional.
         *                    - 'public_key' (string): The associated public key.
         *                    - 'state' (string|null): The state, which will be cast to a SigningKeyState instance.
         *                    - 'expires' (int|DateTime): The expiration time as a timestamp or DateTime object.
         *                    - 'created' (int|DateTime): The creation time as a timestamp or DateTime object.
         * @return void
         */
        public function __construct(array $data)
        {
            $this->peerUuid = $data['peer_uuid'];
            $this->uuid = $data['uuid'];
            $this->name = $data['name'] ?? null;
            $this->publicKey = $data['public_key'];
            $this->state = SigningKeyState::tryFrom($data['state']);

            if(is_int($data['expires']))
            {
                $this->expires = $data['expires'];
            }
            elseif($data['expires'] instanceof DateTime)
            {
                $this->expires = $data['expires']->getTimestamp();
            }
            elseif(is_string($data['expires']))
            {
                if(empty($data['expires']))
                {
                    $this->expires = 0;
                }
                else
                {
                    $this->expires = strtotime($data['expires']);
                }
            }
            elseif($data['expires'] === null)
            {
                $this->expires = 0;
            }
            else
            {
                throw new InvalidArgumentException('Invalid expires value, got type: ' . gettype($data['expires']));
            }

            if(is_int($data['created']))
            {
                $this->created = $data['created'];
            }
            elseif($data['created'] instanceof DateTime)
            {
                $this->created = $data['created']->getTimestamp();
            }
            elseif(is_string($data['created']))
            {
                if(empty($data['created']))
                {
                    $this->created = 0;
                }
                else
                {
                    $this->created = strtotime($data['created']);
                }
            }
            else
            {
                throw new InvalidArgumentException('Invalid created value type, got ' . gettype($data['created']));
            }
        }

        /**
         * Retrieves the UUID of the peer.
         *
         * @return string The UUID of the peer.
         */
        public function getPeerUuid(): string
        {
            return $this->peerUuid;
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
         * Retrieves the name.
         *
         * @return string|null The name, or null if not set.
         */
        public function getName(): ?string
        {
            return $this->name;
        }

        /**
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
         * @return SigningKeyState The state of the signing key.
         */
        public function getState(): SigningKeyState
        {
            return $this->state;
        }

        /**
         * Retrieves the expiration timestamp.
         *
         * @return int The expiration timestamp as an integer.
         */
        public function getExpires(): int
        {
            return $this->expires;
        }

        /**
         *
         * @return int Returns the created timestamp as an integer.
         */
        public function getCreated(): int
        {
            return $this->created;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): SigningKeyRecord
        {
            return new self($data);
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'peer_uuid' => $this->peerUuid,
                'uuid' => $this->uuid,
                'name' => $this->name,
                'public_key' => $this->publicKey,
                'state' => $this->state->value,
                'expires' => (new DateTime())->setTimestamp($this->expires),
                'created' => (new DateTime())->setTimestamp($this->created)
            ];
        }

        /**
         * Converts the current signing key record to its standard format.
         *
         * @return Signature The signing key in its standard format.
         */
        public function toStandard(): Signature
        {
            return Signature::fromSigningKeyRecord($this);
        }
    }