<?php

    namespace Socialbox\Objects\Database;

    use DateTime;
    use Socialbox\Classes\Configuration;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\SessionState;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\RegisteredPeerManager;

    class SessionRecord implements SerializableInterface
    {
        private string $uuid;
        private ?string $peerUuid;
        private string $clientName;
        private string $clientVersion;
        private bool $authenticated;
        private string $clientPublicSigningKey;
        public string $clientPublicEncryptionKey;
        private string $serverPublicEncryptionKey;
        private string $serverPrivateEncryptionKey;
        private ?string $clientTransportEncryptionKey;
        private ?string $serverTransportEncryptionKey;
        private SessionState $state;
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
            $this->clientPublicSigningKey = $data['client_public_signing_key'];
            $this->clientPublicEncryptionKey = $data['client_public_encryption_key'];
            $this->serverPublicEncryptionKey = $data['server_public_encryption_key'];
            $this->serverPrivateEncryptionKey = $data['server_private_encryption_key'];
            $this->clientTransportEncryptionKey = $data['client_transport_encryption_key'] ?? null;
            $this->serverTransportEncryptionKey = $data['server_transport_encryption_key'] ?? null;
            $this->created = $data['created'];
            $this->lastRequest = $data['last_request'];
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
        public function getClientPublicSigningKey(): string
        {
            return $this->clientPublicSigningKey;
        }

        /**
         * Retrieves the encryption key associated with the instance.
         *
         * @return string|null Returns the encryption key as a string, or null if not set.
         */
        public function getClientPublicEncryptionKey(): ?string
        {
            return $this->clientPublicEncryptionKey;
        }

        /**
         * @return string
         */
        public function getServerPublicEncryptionKey(): string
        {
            return $this->serverPublicEncryptionKey;
        }

        /**
         * @return string
         */
        public function getServerPrivateEncryptionKey(): string
        {
            return $this->serverPrivateEncryptionKey;
        }

        /**
         * Retrieves the client encryption key associated with the instance.
         *
         * @return string|null Returns the client encryption key as a string, or null if not set.
         */
        public function getClientTransportEncryptionKey(): ?string
        {
            return $this->clientTransportEncryptionKey;
        }

        /**
         * Retrieves the server encryption key associated with the instance.
         *
         * @return string|null Returns the server encryption key as a string, or null if not set.
         */
        public function getServerTransportEncryptionKey(): ?string
        {
            return $this->serverTransportEncryptionKey;
        }

        /**
         * Retrieves the current session state.
         *
         * @return SessionState Returns the current state of the session.
         */
        public function getState(): SessionState
        {
            $expires = time() + Configuration::getPoliciesConfiguration()->getSessionInactivityExpires();
            if($this->lastRequest !== null && $this->lastRequest->getTimestamp() > $expires)
            {
                return SessionState::EXPIRED;
            }

            return $this->state;
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
         * @return DateTime
         */
        public function getExpires(): DateTime
        {
            return new DateTime('@' . time() + Configuration::getPoliciesConfiguration()->getSessionInactivityExpires());
        }

        /**
         * Retrieves the flags associated with the session.
         *
         * @param bool $asString Determines whether the flags should be returned as strings.
         * @return array An array of session flags, either as objects or strings depending on the $asString parameter.
         */
        public function getFlags(bool $asString=false): array
        {
            if($asString)
            {
                return array_map(fn(SessionFlags $flag) => $flag->value, $this->flags);
            }

            return $this->flags;
        }

        /**
         * Checks if a given flag exists in the list of session flags.
         *
         * @param string|SessionFlags|array $flag The flag to check, either as a string or a SessionFlags object. If an array is provided, all flags must exist.
         * @return bool True if the flag exists, false otherwise.
         */
        public function flagExists(string|SessionFlags|array $flag): bool
        {
            if(is_array($flag))
            {
                foreach($flag as $f)
                {
                    if(!$this->flagExists($f))
                    {
                        return false;
                    }
                }

                return true;
            }

            if(is_string($flag))
            {
                $flag = SessionFlags::tryFrom($flag);
                if($flag === null)
                {
                    return false;
                }
            }

            return in_array($flag, $this->flags);
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

        /**
         * Returns whether the session is external.
         *
         * @return bool True if the session is external, false otherwise.
         * @throws DatabaseOperationException Thrown if the peer record cannot be retrieved.
         */
        public function isExternal(): bool
        {
            $peer = RegisteredPeerManager::getPeer($this->peerUuid);
            if($peer === null)
            {
                return false;
            }
            return $peer->isExternal();
        }

        /**
         * Converts the current session state into a standard session state object.
         *
         * @return \Socialbox\Objects\Standard\SessionState The standardized session state object.
         */
        public function toStandardSessionState(): \Socialbox\Objects\Standard\SessionState
        {
            return \Socialbox\Objects\Standard\SessionState::fromSessionRecord($this);
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
                'peer_uuid' => $this->peerUuid,
                'authenticated' => $this->authenticated,
                'client_public_signing_key' => $this->clientPublicSigningKey,
                'client_public_encryption_key' => $this->clientPublicEncryptionKey,
                'server_public_encryption_key' => $this->serverPublicEncryptionKey,
                'server_private_encryption_key' => $this->serverPrivateEncryptionKey,
                'client_transport_encryption_key' => $this->clientTransportEncryptionKey,
                'server_transport_encryption_key' => $this->serverTransportEncryptionKey,
                'state' => $this->state->value,
                'flags' => SessionFlags::toString($this->flags),
                'created' => $this->created,
                'last_request' => $this->lastRequest,
            ];
        }
    }