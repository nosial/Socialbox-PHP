<?php

    namespace Socialbox\Objects;

    use RuntimeException;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Enums\SessionState;
    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\StandardHeaders;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Managers\SessionManager;

    class ClientRequestOld
    {
        /**
         * @var array
         */
        private array $headers;

        /**
         * @var RpcRequest[]
         */
        private array $requests;

        /**
         * @var string
         */
        private string $requestHash;

        /**
         * ClientRequest constructor.
         *
         * @param array $headers The headers of the request
         * @param RpcRequest[] $requests The RPC requests of the client
         */
        public function __construct(array $headers, array $requests, string $requestHash)
        {
            $this->headers = $headers;
            $this->requests = $requests;
            $this->requestHash = $requestHash;
        }

        /**
         * @return array
         */
        public function getHeaders(): array
        {
            return $this->headers;
        }

        /**
         * @return RpcRequest[]
         */
        public function getRequests(): array
        {
            return $this->requests;
        }

        public function getHash(): string
        {
            return $this->requestHash;
        }

        public function getClientName(): string
        {
            return $this->headers[StandardHeaders::CLIENT_NAME->value];
        }

        public function getClientVersion(): string
        {
            return $this->headers[StandardHeaders::CLIENT_VERSION->value];
        }

        public function getSessionUuid(): ?string
        {
            if(!isset($this->headers[StandardHeaders::SESSION_UUID->value]))
            {
                return null;
            }

            return $this->headers[StandardHeaders::SESSION_UUID->value];
        }

        public function getFromPeer(): ?PeerAddress
        {
            if(!isset($this->headers[StandardHeaders::FROM_PEER->value]))
            {
                return null;
            }

            return PeerAddress::fromAddress($this->headers[StandardHeaders::FROM_PEER->value]);
        }

        public function getSignature(): ?string
        {
            if(!isset($this->headers[StandardHeaders::SIGNATURE->value]))
            {
                return null;
            }

            return $this->headers[StandardHeaders::SIGNATURE->value];
        }

        public function validateSession(): void
        {
            if($this->getSessionUuid() == null)
            {
                throw new StandardException(StandardError::SESSION_REQUIRED->getMessage(), StandardError::SESSION_REQUIRED);
            }

            $session = SessionManager::getSession($this->getSessionUuid());

            switch($session->getState())
            {
                case SessionState::AWAITING_DHE:
                    throw new StandardException(StandardError::SESSION_DHE_REQUIRED->getMessage(), StandardError::SESSION_DHE_REQUIRED);

                case SessionState::EXPIRED:
                    throw new StandardException(StandardError::SESSION_EXPIRED->getMessage(), StandardError::SESSION_EXPIRED);
            }
        }

        /**
         * @return bool
         * @throws DatabaseOperationException
         */
        public function verifySignature(): bool
        {
            $signature = $this->getSignature();
            $sessionUuid = $this->getSessionUuid();

            if($signature == null || $sessionUuid == null)
            {
                return false;
            }

            try
            {
                $session = SessionManager::getSession($sessionUuid);
            }
            catch(StandardException $e)
            {
                if($e->getStandardError() == StandardError::SESSION_NOT_FOUND)
                {
                    return false;
                }

                throw new RuntimeException($e);
            }

            try
            {
                return Cryptography::verifyContent($this->getHash(), $signature, $session->getPublicKey());
            }
            catch(CryptographyException $e)
            {
                return false;
            }
        }
    }