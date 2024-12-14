<?php

    namespace Socialbox\Objects;

    use InvalidArgumentException;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Classes\Utilities;
    use Socialbox\Enums\SessionState;
    use Socialbox\Enums\StandardHeaders;
    use Socialbox\Enums\Types\RequestType;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\RequestException;
    use Socialbox\Managers\RegisteredPeerManager;
    use Socialbox\Managers\SessionManager;
    use Socialbox\Objects\Database\RegisteredPeerRecord;
    use Socialbox\Objects\Database\SessionRecord;

    class ClientRequest
    {
        private array $headers;
        private RequestType $requestType;
        private ?string $requestBody;

        private ?string $clientName;
        private ?string $clientVersion;
        private ?string $identifyAs;
        private ?string $sessionUuid;
        private ?string $signature;

        public function __construct(array $headers, ?string $requestBody)
        {
            $this->headers = $headers;
            $this->requestBody = $requestBody;

            $this->clientName = $headers[StandardHeaders::CLIENT_NAME->value] ?? null;
            $this->clientVersion = $headers[StandardHeaders::CLIENT_VERSION->value] ?? null;
            $this->requestType = RequestType::from($headers[StandardHeaders::REQUEST_TYPE->value]);
            $this->identifyAs = $headers[StandardHeaders::IDENTIFY_AS->value] ?? null;
            $this->sessionUuid = $headers[StandardHeaders::SESSION_UUID->value] ?? null;
            $this->signature = $headers[StandardHeaders::SIGNATURE->value] ?? null;
        }

        public function getHeaders(): array
        {
            return $this->headers;
        }

        public function headerExists(StandardHeaders|string $header): bool
        {
            if(is_string($header))
            {
                return isset($this->headers[$header]);
            }

            return isset($this->headers[$header->value]);
        }

        public function getHeader(StandardHeaders|string $header): ?string
        {
            if(!$this->headerExists($header))
            {
                return null;
            }

            if(is_string($header))
            {
                return $this->headers[$header];
            }

            return $this->headers[$header->value];
        }

        public function getRequestBody(): ?string
        {
            return $this->requestBody;
        }

        public function getClientName(): ?string
        {
            return $this->clientName;
        }

        public function getClientVersion(): ?string
        {
            return $this->clientVersion;
        }

        public function getRequestType(): RequestType
        {
            return $this->requestType;
        }

        public function getIdentifyAs(): ?PeerAddress
        {
            if($this->identifyAs === null)
            {
                return null;
            }

            return PeerAddress::fromAddress($this->identifyAs);
        }

        public function getSessionUuid(): ?string
        {
            return $this->sessionUuid;
        }

        public function getSession(): ?SessionRecord
        {
            if($this->sessionUuid === null)
            {
                return null;
            }

            return SessionManager::getSession($this->sessionUuid);
        }

        public function getPeer(): ?RegisteredPeerRecord
        {
            $session = $this->getSession();

            if($session === null)
            {
                return null;
            }

            return RegisteredPeerManager::getPeer($session->getPeerUuid());
        }

        public function getSignature(): ?string
        {
            return $this->signature;
        }

        private function verifySignature(string $decryptedContent): bool
        {
            if($this->getSignature() == null || $this->getSessionUuid() == null)
            {
                return false;
            }

            try
            {
                return Cryptography::verifyContent(hash('sha1', $decryptedContent), $this->getSignature(), $this->getSession()->getPublicKey());
            }
            catch(CryptographyException)
            {
                return false;
            }
        }

        /**
         * Handles a POST request, returning an array of RpcRequest objects
         * expects a JSON encoded body with either a single RpcRequest object or an array of RpcRequest objects
         *
         * @return RpcRequest[] The parsed RpcRequest objects
         * @throws RequestException Thrown if the request is invalid
         */
        public function getRpcRequests(): array
        {
            if($this->getSessionUuid() === null)
            {
                throw new RequestException("Session UUID required", 400);
            }

            // Get the existing session
            $session = $this->getSession();

            // If we're awaiting a DHE, encryption is not possible at this point
            if($session->getState() === SessionState::AWAITING_DHE)
            {
                throw new RequestException("DHE exchange required", 400);
            }

            // If the session is not active, we can't serve these requests
            if($session->getState() !== SessionState::ACTIVE)
            {
                throw new RequestException("Session is not active", 400);
            }

            // Attempt to decrypt the content and verify the signature of the request
            try
            {
                $decrypted = Cryptography::decryptTransport($this->requestBody, $session->getEncryptionKey());

                if(!$this->verifySignature($decrypted))
                {
                    throw new RequestException("Invalid request signature", 401);
                }
            }
            catch (CryptographyException $e)
            {
                throw new RequestException("Failed to decrypt request body", 400, $e);
            }

            // At this stage, all checks has passed; we can try parsing the RPC request
            try
            {
                // Decode the request body
                $body = Utilities::jsonDecode($decrypted);
            }
            catch(InvalidArgumentException $e)
            {
                throw new RequestException("Invalid JSON in request body: " . $e->getMessage(), 400, $e);
            }

            // If the body only contains a method, we assume it's a single request
            if(isset($body['method']))
            {
                return [$this->parseRequest($body)];
            }

            // Otherwise, we assume it's an array of requests
            return array_map(fn($request) => $this->parseRequest($request), $body);
        }

        /**
         * Parses the raw request data into an RpcRequest object
         *
         * @param array $data The raw request data
         * @return RpcRequest The parsed RpcRequest object
         * @throws RequestException If the request is invalid
         */
        private function parseRequest(array $data): RpcRequest
        {
            if(!isset($data['method']))
            {
                throw new RequestException("Missing 'method' key in request", 400);
            }

            if(isset($data['id']))
            {
                if(!is_string($data['id']))
                {
                    throw new RequestException("Invalid 'id' key in request: Expected string", 400);
                }

                if(strlen($data['id']) === 0)
                {
                    throw new RequestException("Invalid 'id' key in request: Expected non-empty string", 400);
                }

                if(strlen($data['id']) > 8)
                {
                    throw new RequestException("Invalid 'id' key in request: Expected string of length <= 8", 400);
                }
            }

            if(isset($data['parameters']))
            {
                if(!is_array($data['parameters']))
                {
                    throw new RequestException("Invalid 'parameters' key in request: Expected array", 400);
                }
            }

            return new RpcRequest($data['method'], $data['id'] ?? null, $data['parameters'] ?? null);
        }
    }