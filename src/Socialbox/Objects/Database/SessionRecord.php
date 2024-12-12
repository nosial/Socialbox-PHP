<?php

    namespace Socialbox\Objects\Database;

    use DateTime;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\SessionState;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\RegisteredPeerManager;

    class SessionRecord implements SerializableInterface
    {
        private string $uuid;
        private ?string $peerUuid;
        private string $clientName;
        private string $clientVersion;
        private bool $authenticated;
        private string $publicKey;
        private SessionState $state;
        private ?string $encryptionKey;
        /**
         * @var SessionFlags[]
         */
        private array $flags;
        private DateTime $created;
        private ?DateTime $lastRequest;

        /**
         * Constructs a new instance using the provided data array.
         *
         * @param array $data An associative array containing the initialization data,
         *                    which should include keys such as 'uuid', 'peer_uuid',
         *                    'authenticated', 'public_key', 'created', 'last_request',
         *                    'flags', and 'state'.
         *
         * @return void
         */
        public function __construct(array $data)
        {
            $this->uuid = $data['uuid'];
            $this->peerUuid = $data['peer_uuid'] ?? null;
            $this->clientName = $data['client_name'];
            $this->clientVersion = $data['client_version'];
            $this->authenticated = $data['authenticated'] ?? false;
            $this->publicKey = $data['public_key'];
            $this->created = $data['created'];
            $this->lastRequest = $data['last_request'];
            $this->encryptionKey = $data['encryption_key'] ?? null;
            $this->flags = SessionFlags::fromString($data['flags']);

            if(SessionState::tryFrom($data['state']) == null)
            {
                $this->state = SessionState::CLOSED;
            }
            else
            {
                $this->state = SessionState::from($data['state']);
            }

        }

        /**
         * Retrieves the UUID.
         *
         * @return string The UUID of the object.
         */
        public function getUuid(): string
        {
            return $this->uuid;
        }

        /**
         * Retrieves the UUID of the peer.
         *
         * @return string|null The UUID of the peer or null if not set.
         */
        public function getPeerUuid(): ?string
        {
            return $this->peerUuid;
        }

        /**
         * Checks whether the user is authenticated.
         *
         * @return bool Returns true if the user is authenticated; otherwise, false.
         */
        public function isAuthenticated(): bool
        {
            if($this->peerUuid === null)
            {
                return false;
            }

            return $this->authenticated;
        }

        /**
         * Retrieves the public key associated with the instance.
         *
         * @return string Returns the public key as a string.
         */
        public function getPublicKey(): string
        {
            return $this->publicKey;
        }

        /**
         * Retrieves the current session state.
         *
         * @return SessionState Returns the current state of the session.
         */
        public function getState(): SessionState
        {
            return $this->state;
        }

        /**
         * Retrieves the encryption key associated with the instance.
         *
         * @return string|null Returns the encryption key as a string.
         */
        public function getEncryptionKey(): ?string
        {
            return $this->encryptionKey;
        }

        /**
         * Retrieves the creation date and time of the object.
         *
         * @return DateTime Returns a DateTime object representing when the object was created.
         */
        public function getCreated(): DateTime
        {
            return $this->created;
        }

        /**
         * Retrieves the list of flags associated with the current instance.
         *
         * @return array Returns an array of flags.
         */
        public function getFlags(): array
        {
            return $this->flags;
        }

        /**
         * Retrieves the timestamp of the last request made.
         *
         * @return DateTime|null The DateTime object representing the last request time, or null if no request has been made.
         */
        public function getLastRequest(): ?DateTime
        {
            return $this->lastRequest;
        }

        /**
         * Retrieves the client name.
         *
         * @return string Returns the client name.
         */
        public function getClientName(): string
        {
            return $this->clientName;
        }

        /**
         * Retrieves the client version.
         *
         * @return string Returns the client version.
         */
        public function getClientVersion(): string
        {
            return $this->clientVersion;
        }

        public function toStandardSessionState(): \Socialbox\Objects\Standard\SessionState
        {
            return new \Socialbox\Objects\Standard\SessionState([
                'uuid' => $this->uuid,
                'identified_as' => RegisteredPeerManager::getPeer($this->peerUuid)->getAddress(),
                'authenticated' => $this->authenticated,
                'flags' => $this->flags,
                'created' => $this->created
            ]);
        }
        
        
        /**
         * Creates a new instance of the class using the provided array data.
         *
         * @param array $data An associative array of data used to initialize the object properties.
         * @return object Returns a newly created object instance.
         */
        public static function fromArray(array $data): object
        {
            return new self($data);
        }

        /**
         * Converts the object's properties to an associative array.
         *
         * @return array An associative array representing the object's data, including keys 'uuid', 'peer_uuid',
         *               'authenticated', 'public_key', 'state', 'flags', 'created', and 'last_request'.
         */
        public function toArray(): array
        {
            return [
                'uuid' => $this->uuid,
                'peer_uuid' => $this->peerUuid,
                'authenticated' => $this->authenticated,
                'public_key' => $this->publicKey,
                'state' => $this->state->value,
                'flags' => SessionFlags::toString($this->flags),
                'created' => $this->created,
                'last_request' => $this->lastRequest,
            ];
        }
    }