<?php

    namespace Socialbox\Classes;

    use Socialbox\Enums\StandardHeaders;
    use Socialbox\Enums\Types\RequestType;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\ResolutionException;
    use Socialbox\Exceptions\RpcException;
    use Socialbox\Objects\ExportedSession;
    use Socialbox\Objects\KeyPair;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\RpcRequest;
    use Socialbox\Objects\RpcResult;

    class RpcClient
    {
        private const string CLIENT_NAME = 'Socialbox PHP';
        private const string CLIENT_VERSION = '1.0';

        private bool $bypassSignatureVerification;
        private PeerAddress $peerAddress;
        private KeyPair $keyPair;
        private string $encryptionKey;
        private string $serverPublicKey;
        private string $rpcEndpoint;
        private string $sessionUuid;

        /**
         * Constructs a new instance with the specified peer address.
         *
         * @param string|PeerAddress $peerAddress The peer address to be used for the instance (eg; johndoe@example.com)
         * @param ExportedSession|null $exportedSession Optional. An exported session to be used to re-connect.
         * @throws CryptographyException If there is an error in the cryptographic operations.
         * @throws RpcException If there is an error in the RPC request or if no response is received.
         * @throws ResolutionException If there is an error in the resolution process.
         */
        public function __construct(string|PeerAddress $peerAddress, ?ExportedSession $exportedSession=null)
        {
            $this->bypassSignatureVerification = false;

            // If an exported session is provided, no need to re-connect.
            if($exportedSession !== null)
            {
                $this->peerAddress = PeerAddress::fromAddress($exportedSession->getPeerAddress());
                $this->keyPair = new KeyPair($exportedSession->getPublicKey(), $exportedSession->getPrivateKey());
                $this->encryptionKey = $exportedSession->getEncryptionKey();
                $this->serverPublicKey = $exportedSession->getServerPublicKey();
                $this->rpcEndpoint = $exportedSession->getRpcEndpoint();
                $this->sessionUuid = $exportedSession->getSessionUuid();
                return;
            }

            // If the peer address is a string, we need to convert it to a PeerAddress object
            if(is_string($peerAddress))
            {
                $peerAddress = PeerAddress::fromAddress($peerAddress);
            }

            // Set the initial properties
            $this->peerAddress = $peerAddress;
            $this->keyPair = Cryptography::generateKeyPair();
            $this->encryptionKey = Cryptography::generateEncryptionKey();

            // Resolve the domain and get the server's Public Key & RPC Endpoint
            try
            {
                $resolvedServer = ServerResolver::resolveDomain($this->peerAddress->getDomain(), false);
            }
            catch (DatabaseOperationException $e)
            {
                throw new ResolutionException('Failed to resolve domain: ' . $e->getMessage(), 0, $e);
            }

            $this->serverPublicKey = $resolvedServer->getPublicKey();
            $this->rpcEndpoint = $resolvedServer->getEndpoint();

            if(empty($this->serverPublicKey))
            {
                throw new ResolutionException('Failed to resolve domain: No public key found for the server');
            }

            // Attempt to create an encrypted session with the server
            $this->sessionUuid = $this->createSession();
            $this->sendDheExchange();
        }

        /**
         * Creates a new session by sending an HTTP GET request to the RPC endpoint.
         * The request includes specific headers required for session initiation.
         *
         * @return string Returns the session UUID received from the server.
         * @throws RpcException If the server response is invalid, the session creation fails, or no session UUID is returned.
         */
        private function createSession(): string
        {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $this->rpcEndpoint);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                StandardHeaders::REQUEST_TYPE->value . ': ' . RequestType::INITIATE_SESSION->value,
                StandardHeaders::CLIENT_NAME->value . ': ' . self::CLIENT_NAME,
                StandardHeaders::CLIENT_VERSION->value . ': ' . self::CLIENT_VERSION,
                StandardHeaders::PUBLIC_KEY->value . ': ' . $this->keyPair->getPublicKey(),
                StandardHeaders::IDENTIFY_AS->value . ': ' . $this->peerAddress->getAddress(),
            ]);

            $response = curl_exec($ch);

            if($response === false)
            {
                curl_close($ch);
                throw new RpcException('Failed to create the session, no response received');
            }

            $responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            if($responseCode !== 201)
            {
                curl_close($ch);
                throw new RpcException('Failed to create the session, server responded with ' . $responseCode . ': ' . $response);
            }

            if(empty($response))
            {
                curl_close($ch);
                throw new RpcException('Failed to create the session, server did not return a session UUID');
            }

            curl_close($ch);
            return $response;
        }

        /**
         * Sends a Diffie-Hellman Ephemeral (DHE) exchange request to the server.
         *
         * @throws RpcException If the encryption or the request fails.
         */
        private function sendDheExchange(): void
        {
            // Request body should contain the encrypted key, the client's public key, and the session UUID
            // Upon success the server should return 204 without a body
            try
            {
                $encryptedKey = Cryptography::encryptContent($this->encryptionKey, $this->serverPublicKey);
            }
            catch (CryptographyException $e)
            {
                throw new RpcException('Failed to encrypt DHE exchange data', 0, $e);
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->rpcEndpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                StandardHeaders::REQUEST_TYPE->value . ': ' . RequestType::DHE_EXCHANGE->value,
                StandardHeaders::SESSION_UUID->value . ': ' . $this->sessionUuid,
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $encryptedKey);

            $response = curl_exec($ch);

            if($response === false)
            {
                curl_close($ch);
                throw new RpcException('Failed to send DHE exchange, no response received');
            }

            $responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            if($responseCode !== 204)
            {
                curl_close($ch);
                throw new RpcException('Failed to send DHE exchange, server responded with ' . $responseCode . ': ' . $response);
            }

            curl_close($ch);
        }

        /**
         * Sends an RPC request with the given JSON data.
         *
         * @param string $jsonData The JSON data to be sent in the request.
         * @return RpcResult[] An array of RpcResult objects.
         * @throws RpcException If the request fails, the response is invalid, or the decryption/signature verification fails.
         */
        public function sendRawRequest(string $jsonData): array
        {
            try
            {
                $encryptedData = Cryptography::encryptTransport($jsonData, $this->encryptionKey);
                $signature = Cryptography::signContent($jsonData, $this->keyPair->getPrivateKey(), true);
            }
            catch (CryptographyException $e)
            {
                throw new RpcException('Failed to encrypt request data: ' . $e->getMessage(), 0, $e);
            }

            $ch = curl_init();
            $headers = [];

            curl_setopt($ch, CURLOPT_URL, $this->rpcEndpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$headers)
            {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                {
                    return $len;
                }

                $headers[strtolower(trim($header[0]))][] = trim($header[1]);
                return $len;
            });
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                StandardHeaders::REQUEST_TYPE->value . ': ' . RequestType::RPC->value,
                StandardHeaders::SESSION_UUID->value . ': ' . $this->sessionUuid,
                StandardHeaders::SIGNATURE->value . ': ' . $signature
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $encryptedData);

            $response = curl_exec($ch);

            if ($response === false)
            {
                curl_close($ch);
                throw new RpcException('Failed to send request, no response received');
            }

            $responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $responseString = $response;

            if (!Utilities::isSuccessCodes($responseCode))
            {
                curl_close($ch);
                if (!empty($responseString))
                {
                    throw new RpcException($responseString);
                }

                throw new RpcException('Failed to send request (Empty Response): ' . $responseCode);
            }

            if ($responseCode == 204)
            {
                curl_close($ch);
                return [];
            }

            if (empty($responseString))
            {
                curl_close($ch);
                throw new RpcException('The request was successful but the server did not indicate an empty response');
            }

            curl_close($ch);

            try
            {
                $decryptedResponse = Cryptography::decryptTransport($responseString, $this->encryptionKey);
            }
            catch (CryptographyException $e)
            {
                throw new RpcException('Failed to decrypt response: ' . $e->getMessage(), 0, $e);
            }

            if (!$this->bypassSignatureVerification)
            {
                $signature = $headers['signature'][0] ?? null;
                if ($signature === null)
                {
                    throw new RpcException('The server did not provide a signature for the response');
                }

                try
                {
                    if (!Cryptography::verifyContent($decryptedResponse, $signature, $this->serverPublicKey, true))
                    {
                        throw new RpcException('Failed to verify the response signature');
                    }
                }
                catch (CryptographyException $e)
                {
                    throw new RpcException('Failed to verify the response signature: ' . $e->getMessage(), 0, $e);
                }
            }

            $decoded = json_decode($decryptedResponse, true);
            if(isset($decoded['id']))
            {
                return [new RpcResult($decoded)];
            }
            else
            {
                $results = [];
                foreach ($decoded as $responseMap)
                {
                    $results[] = new RpcResult($responseMap);
                }
                return $results;
            }
        }

        /**
         * Sends an RPC request and retrieves the corresponding RPC response.
         *
         * @param RpcRequest $request The RPC request to be sent.
         * @return RpcResult The received RPC response.
         * @throws RpcException If no response is received from the request.
         */
        public function sendRequest(RpcRequest $request, bool $throwException=true): RpcResult
        {
            $response = $this->sendRawRequest(json_encode($request->toArray()));

            if (count($response) === 0)
            {
                throw new RpcException('Failed to send request, no response received');
            }

            if($throwException)
            {
                if($response[0]->getError() !== null)
                {
                    throw $response[0]->getError()->toRpcException();
                }
            }

            return $response[0];
        }

        /**
         * Sends a batch of requests to the server, processes them into an appropriate format,
         * and handles the response.
         *
         * @param RpcRequest[] $requests An array of RpcRequest objects to be sent to the server.
         * @return RpcResult[] An array of RpcResult objects received from the server.
         * @throws RpcException If no response is received from the server.
         */
        public function sendRequests(array $requests): array
        {
            $parsedRequests = [];
            foreach ($requests as $request)
            {
                $parsedRequests[] = $request->toArray();
            }

            $responses = $this->sendRawRequest(json_encode($parsedRequests));

            if (count($responses) === 0)
            {
                return [];
            }

            return $responses;
        }

        /**
         * Exports the current session details into an ExportedSession object.
         *
         * @return ExportedSession The exported session containing session-specific details.
         */
        public function exportSession(): ExportedSession
        {
            return new ExportedSession([
                'peer_address' => $this->peerAddress->getAddress(),
                'private_key' => $this->keyPair->getPrivateKey(),
                'public_key' => $this->keyPair->getPublicKey(),
                'encryption_key' => $this->encryptionKey,
                'server_public_key' => $this->serverPublicKey,
                'rpc_endpoint' => $this->rpcEndpoint,
                'session_uuid' => $this->sessionUuid
            ]);
        }
    }