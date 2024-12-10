<?php

    namespace Socialbox\Classes;

    use Socialbox\Enums\StandardHeaders;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\ResolutionException;
    use Socialbox\Exceptions\RpcException;
    use Socialbox\Objects\RpcError;
    use Socialbox\Objects\RpcRequest;
    use Socialbox\Objects\RpcResponse;

    class RpcClient
    {
        private const string CLIENT_NAME = 'Socialbox PHP';
        private const string CLIENT_VERSION = '1.0';
        private const string CONTENT_TYPE = 'application/json; charset=utf-8';

        private string $domain;
        private string $endpoint;
        private string $serverPublicKey;
        private ?string $sessionUuid;
        private ?string $privateKey;

        /**
         * Constructor for initializing the server connection with a given domain.
         *
         * @param string $domain The domain used to resolve the server's endpoint and public key.
         * @throws ResolutionException
         * @noinspection PhpUnhandledExceptionInspection
         */
        public function __construct(string $domain)
        {
            $resolved = ServerResolver::resolveDomain($domain);

            $this->domain = $domain;
            $this->endpoint = $resolved->getEndpoint();
            $this->serverPublicKey = $resolved->getPublicKey();
            $this->sessionUuid = null;
            $this->privateKey = null;
        }

        /**
         * Retrieves the domain.
         *
         * @return string The domain.
         */
        public function getDomain(): string
        {
            return $this->domain;
        }

        /**
         * Retrieves the endpoint URL.
         *
         * @return string The endpoint URL.
         */
        public function getEndpoint(): string
        {
            return $this->endpoint;
        }

        /**
         * Retrieves the server's public key.
         *
         * @return string The server's public key.
         */
        public function getServerPublicKey(): string
        {
            return $this->serverPublicKey;
        }

        /**
         * Retrieves the session UUID.
         *
         * @return string|null The session UUID or null if not set.
         */
        public function getSessionUuid(): ?string
        {
            return $this->sessionUuid;
        }

        /**
         * Sets the session UUID.
         *
         * @param string|null $sessionUuid The session UUID to set. Can be null.
         * @return void
         */
        public function setSessionUuid(?string $sessionUuid): void
        {
            $this->sessionUuid = $sessionUuid;
        }

        /**
         * Retrieves the private key.
         *
         * @return string|null The private key if available, or null if not set.
         */
        public function getPrivateKey(): ?string
        {
            return $this->privateKey;
        }

        /**
         * Sets the private key.
         *
         * @param string|null $privateKey The private key to be set. Can be null.
         * @return void
         */
        public function setPrivateKey(?string $privateKey): void
        {
            $this->privateKey = $privateKey;
        }

        /**
         * Sends an RPC request to the specified endpoint.
         *
         * @param RpcRequest $request The RPC request to be sent.
         * @return RpcResponse|RpcError|null The response from the RPC server, an error object, or null if no content.
         * @throws CryptographyException If an error occurs during the signing of the content.
         * @throws RpcException If an error occurs while sending the request or processing the response.
         */
        public function sendRequest(RpcRequest $request): RpcResponse|RpcError|null
        {
            $curl = curl_init($this->endpoint);
            $content = Utilities::jsonEncode($request->toArray());
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $this->getHeaders($content),
                CURLOPT_POSTFIELDS => $content,
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if(curl_errno($curl))
            {
                throw new RpcException(sprintf('Failed to send request: %s', curl_error($curl)));
            }

            curl_close($curl);

            // Return null if the response is empty
            if($httpCode === 204)
            {
                return null;
            }

            if(!$this->isSuccessful($httpCode))
            {
                if(!empty($response))
                {
                    throw new RpcException($response);
                }

                throw new RpcException(sprintf('Error occurred while processing request: %d', $httpCode));
            }

            if(empty($response))
            {
                throw new RpcException('Response was empty but status code was successful');
            }

            return RpcResponse::fromArray(Utilities::jsonDecode($response));
        }

        /**
         * Sends multiple requests to the designated endpoint and returns their responses.
         *
         * @param array $requests An array of request objects, each implementing the method toArray().
         * @return RpcResponse[]|RpcError[] An array of response objects, each implementing the method toArray().
         * @throws CryptographyException If an error occurs during the signing of the content.
         * @throws RpcException If any errors occur during the request process or in case of unsuccessful HTTP codes.
         */
        public function sendRequests(array $requests): array
        {
            $curl = curl_init($this->endpoint);
            $contents = null;

            foreach($requests as $request)
            {
                $contents[] = $request->toArray();
            }

            $content = Utilities::jsonEncode($contents);

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $this->getHeaders($content),
                CURLOPT_POSTFIELDS => $content,
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if(curl_errno($curl))
            {
                throw new RpcException(sprintf('Failed to send request: %s', curl_error($curl)));
            }

            curl_close($curl);

            // Return null if the response is empty
            if($httpCode === 204)
            {
                return [];
            }

            if(!$this->isSuccessful($httpCode))
            {
                if(!empty($response))
                {
                    throw new RpcException($response);
                }

                throw new RpcException(sprintf('Error occurred while processing request: %d', $httpCode));
            }

            if(empty($response))
            {
                throw new RpcException('Response was empty but status code was successful');
            }

            $results = Utilities::jsonDecode($response);
            $responses = [];

            foreach($results as $result)
            {
                $responses[] = RpcResponse::fromArray($result);
            }

            return $responses;
        }

        /**
         * Determines if the provided HTTP status code indicates a successful response.
         *
         * @param int $code The HTTP status code to evaluate.
         * @return bool True if the status code represents success (2xx), false otherwise.
         */
        private function isSuccessful(int $code): bool
        {
            return $code >= 200 && $code < 300;
        }

        /**
         * Generates an array of headers based on standard headers and instance-specific properties.
         *
         * @param string $content The content to be signed if a private key is available.
         * @return array An array of headers to be included in an HTTP request.
         * @throws CryptographyException If an error occurs during the signing of the content.
         */
        private function getHeaders(string $content): array
        {
            $headers = [
                sprintf('%s: %s', StandardHeaders::CLIENT_NAME->value, self::CLIENT_NAME),
                sprintf('%s: %s', StandardHeaders::CLIENT_VERSION->value, self::CLIENT_VERSION),
                sprintf('%s: %s', StandardHeaders::CONTENT_TYPE->value, self::CONTENT_TYPE),
            ];

            if($this->sessionUuid !== null)
            {
                $headers[] = sprintf('%s: %s', StandardHeaders::SESSION_UUID->value, $this->sessionUuid);
            }

            if($this->privateKey !== null)
            {
                try
                {
                    $headers[] = sprintf('%s: %s', StandardHeaders::SIGNATURE->value, Cryptography::signContent($content, $this->privateKey, true));
                }
                catch (CryptographyException $e)
                {
                    Logger::getLogger()->error('Failed to sign content: ' . $e->getMessage());
                    throw $e;
                }
            }

            return $headers;
        }
    }