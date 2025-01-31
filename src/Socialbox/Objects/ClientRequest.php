<?php

    namespace Socialbox\Objects;

    use Socialbox\Classes\Cryptography;
    use Socialbox\Enums\StandardHeaders;
    use Socialbox\Enums\Types\RequestType;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\RequestException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Managers\RegisteredPeerManager;
    use Socialbox\Managers\SessionManager;
    use Socialbox\Objects\Database\PeerDatabaseRecord;
    use Socialbox\Objects\Database\SessionRecord;

    class ClientRequest
    {
        private array $headers;
        private ?RequestType $requestType;
        private ?string $requestBody;

        private ?string $clientName;
        private ?string $clientVersion;
        private ?string $identifyAs;
        private ?string $sessionUuid;
        private ?string $signature;

        /**
         * Initializes the instance with the provided request headers and optional request body.
         *
         * @param array $headers An associative array of request headers used to set properties such as client name, version, and others.
         * @param string|null $requestBody The optional body of the request, or null if not provided.
         *
         * @return void
         */
        public function __construct(array $headers, ?string $requestBody)
        {
            $parsedHeaders = [];
            foreach($headers as $key => $value)
            {
                $parsedHeaders[strtolower($key)] = $value;
            }

            $this->headers = $parsedHeaders;
            $this->requestBody = $requestBody;

            $this->clientName = $parsedHeaders[strtolower(StandardHeaders::CLIENT_NAME->value)] ?? null;
            $this->clientVersion = $parsedHeaders[strtolower(StandardHeaders::CLIENT_VERSION->value)] ?? null;
            $this->requestType = RequestType::tryFrom($parsedHeaders[strtolower(StandardHeaders::REQUEST_TYPE->value)]);
            $this->identifyAs = $parsedHeaders[strtolower(StandardHeaders::IDENTIFY_AS->value)] ?? null;
            $this->sessionUuid = $parsedHeaders[strtolower(StandardHeaders::SESSION_UUID->value)] ?? null;
            $this->signature = $parsedHeaders[strtolower(StandardHeaders::SIGNATURE->value)] ?? null;
        }

        /**
         * Retrieves the headers.
         *
         * @return array Returns an array of headers.
         */
        public function getHeaders(): array
        {
            return $this->headers;
        }

        /**
         * Checks if the specified header exists in the collection of headers.
         *
         * @param StandardHeaders|string $header The header to check, either as a StandardHeaders enum or a string.
         * @return bool Returns true if the header exists, otherwise false.
         */
        public function headerExists(StandardHeaders|string $header): bool
        {
            if(is_string($header))
            {
                return isset($this->headers[strtolower($header)]);
            }

            return isset($this->headers[strtolower($header->value)]);
        }

        /**
         * Retrieves the value of a specified header.
         *
         * @param StandardHeaders|string $header The header to retrieve, provided as either a StandardHeaders enum or a string key.
         * @return string|null Returns the header value if it exists, or null if the header does not exist.
         */
        public function getHeader(StandardHeaders|string $header): ?string
        {
            if(!$this->headerExists($header))
            {
                return null;
            }

            if(is_string($header))
            {
                return $this->headers[strtolower($header)];
            }

            return $this->headers[strtolower($header->value)];
        }

        /**
         * Retrieves the request body.
         *
         * @return string|null Returns the request body as a string if available, or null if not set.
         */
        public function getRequestBody(): ?string
        {
            return $this->requestBody;
        }

        /**
         * Retrieves the name of the client.
         *
         * @return string|null Returns the client's name if set, or null if not available.
         */
        public function getClientName(): ?string
        {
            return $this->clientName;
        }

        /**
         * Retrieves the client version.
         *
         * @return string|null Returns the client version if available, or null if not set.
         */
        public function getClientVersion(): ?string
        {
            return $this->clientVersion;
        }

        /**
         * Retrieves the request type associated with the current instance.
         *
         * @return RequestType|null Returns the associated RequestType if available, or null if not set.
         */
        public function getRequestType(): ?RequestType
        {
            return $this->requestType;
        }

        /**
         * Retrieves the peer address the instance identifies as.
         *
         * @return PeerAddress|null Returns a PeerAddress instance if the identification address is set, or null otherwise.
         */
        public function getIdentifyAs(): ?PeerAddress
        {
            if($this->identifyAs === null)
            {
                return null;
            }

            return PeerAddress::fromAddress($this->identifyAs);
        }

        /**
         * Retrieves the UUID of the current session.
         *
         * @return string|null Returns the session UUID if available, or null if it is not set.
         */
        public function getSessionUuid(): ?string
        {
            return $this->sessionUuid;
        }

        /**
         * Retrieves the current session associated with the session UUID.
         *
         * @return SessionRecord|null Returns the associated SessionRecord if the session UUID exists, or null if no session UUID is set.
         * @throws DatabaseOperationException Thrown if an error occurs while retrieving the session.
         * @throws StandardRpcException Thrown if the session UUID is invalid.
         */
        public function getSession(): ?SessionRecord
        {
            if($this->sessionUuid === null)
            {
                return null;
            }

            return SessionManager::getSession($this->sessionUuid);
        }

        /**
         * Retrieves the associated peer for the current session.
         *
         * @return PeerDatabaseRecord|null Returns the associated RegisteredPeerRecord if available, or null if no session exists.
         * @throws DatabaseOperationException Thrown if an error occurs while retrieving the peer.
         * @throws StandardRpcException Thrown if the session UUID is invalid.
         */
        public function getPeer(): ?PeerDatabaseRecord
        {
            $session = $this->getSession();

            if($session === null)
            {
                return null;
            }

            return RegisteredPeerManager::getPeer($session->getPeerUuid());
        }

        /**
         * Retrieves the signature value.
         *
         * @return string|null The signature value or null if not set
         */
        public function getSignature(): ?string
        {
            return $this->signature;
        }

        /**
         * Verifies the signature of the provided decrypted content.
         *
         * @param string $decryptedContent The decrypted content to verify the signature against.
         * @return bool True if the signature is valid, false otherwise.
         * @throws DatabaseOperationException Thrown if an error occurs while retrieving the client's public signing key.
         * @throws StandardRpcException Thrown if the session UUID is invalid.
         */
        private function verifySignature(string $decryptedContent): bool
        {
            if($this->getSignature() == null || $this->getSessionUuid() == null)
            {
                return false;
            }

            try
            {
                return Cryptography::verifyMessage(
                    message: $decryptedContent,
                    signature: $this->getSignature(),
                    publicKey: $this->getSession()->getClientPublicSigningKey()
                );
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
        public function getRpcRequests(string $json): array
        {
            $body = json_decode($json, true);
            if($body === false)
            {
                throw new RequestException('Malformed JSON', 400);
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