<?php

    namespace Socialbox\Classes;

    use Socialbox\Classes\ServerResolver;
    use Socialbox\Enums\StandardHeaders;
    use Socialbox\Exceptions\ResolutionException;
    use Socialclient\Exceptions\RpcRequestException;

    class RpcClient
    {
        private const string CLIENT_NAME = 'Socialbox PHP';
        private const string CLIENT_VERSION = '1.0';
        private const string CONTENT_TYPE = 'application/json';

        private string $domain;
        private string $endpoint;
        private string $serverPublicKey;


        /**
         * @throws ResolutionException
         */
        public function __construct(string $domain)
        {
            $resolved = ServerResolver::resolveDomain($domain);

            $this->domain = $domain;
            $this->endpoint = $resolved->getEndpoint();
            $this->serverPublicKey = $resolved->getPublicKey();
            $this->clientPrivateKey = null;
        }

        public function getDomain(): string
        {
            return $this->domain;
        }

        public function getEndpoint(): string
        {
            return $this->endpoint;
        }

        public function getServerPublicKey(): string
        {
            return $this->serverPublicKey;
        }

        public function sendRequest(array $data)
        {
            $ch = curl_init($this->endpoint);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, Utilities::jsonEncode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                Utilities::generateHeader(StandardHeaders::CLIENT_NAME, self::CLIENT_NAME),
                Utilities::generateHeader(StandardHeaders::CLIENT_VERSION, self::CLIENT_VERSION),
                Utilities::generateHeader(StandardHeaders::CONTENT_TYPE, self::CONTENT_TYPE)
            ]);
            curl_setopt($ch, CURLOPT_HEADER, true);

            $response = curl_exec($ch);

            if (curl_errno($ch))
            {
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                // Separate headers and body
                $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $response_body = substr($response, $header_size);

                curl_close($ch);

                // Throw exception with response body as message and status code as code
                throw new RpcRequestException($response_body, $statusCode);
            }

            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            // Separate headers and body
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $response_headers = substr($response, 0, $header_size);
            $response_body = substr($response, $header_size);

            curl_close($ch);
        }
    }