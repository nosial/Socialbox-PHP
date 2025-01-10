<?php

    namespace Socialbox\Classes;

    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\StandardHeaders;
    use Socialbox\Enums\Types\RequestType;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\ResolutionException;
    use Socialbox\Exceptions\RpcException;
    use Socialbox\Objects\ExportedSession;
    use Socialbox\Objects\KeyPair;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\RpcRequest;
    use Socialbox\Objects\RpcResult;
    use Socialbox\Objects\Standard\ServerInformation;

    class RpcClient
    {
        private const string CLIENT_NAME = 'Socialbox PHP';
        private const string CLIENT_VERSION = '1.0';

        private bool $bypassSignatureVerification;
        private PeerAddress $peerAddress;
        private string $serverPublicSigningKey;
        private string $serverPublicEncryptionKey;
        private KeyPair $clientSigningKeyPair;
        private KeyPair $clientEncryptionKeyPair;
        private string $privateSharedSecret;
        private string $clientTransportEncryptionKey;
        private string $serverTransportEncryptionKey;
        private ServerInformation $serverInformation;
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
            // Just use the session details provided.
            if($exportedSession !== null)
            {
                // Check if the server keypair has expired from the exported session
                if($exportedSession->getServerKeypairExpires() > 0 && time() > $exportedSession->getServerKeypairExpires())
                {
                    throw new RpcException('The server keypair has expired, a new session must be created');
                }

                $this->peerAddress = PeerAddress::fromAddress($exportedSession->getPeerAddress());
                $this->rpcEndpoint = $exportedSession->getRpcEndpoint();
                $this->sessionUuid = $exportedSession->getSessionUuid();
                $this->serverPublicSigningKey = $exportedSession->getServerPublicSigningKey();
                $this->serverPublicEncryptionKey = $exportedSession->getServerPublicEncryptionKey();
                $this->clientSigningKeyPair = new KeyPair($exportedSession->getClientPublicSigningKey(), $exportedSession->getClientPrivateSigningKey());
                $this->clientEncryptionKeyPair = new KeyPair($exportedSession->getClientPublicEncryptionKey(), $exportedSession->getClientPrivateEncryptionKey());
                $this->privateSharedSecret = $exportedSession->getPrivateSharedSecret();
                $this->clientTransportEncryptionKey = $exportedSession->getClientTransportEncryptionKey();
                $this->serverTransportEncryptionKey = $exportedSession->getServerTransportEncryptionKey();

                // Still solve the server information
                $this->serverInformation = self::getServerInformation();

                // Check if the active keypair has expired
                if($this->serverInformation->getServerKeypairExpires() > 0 && time() > $this->serverInformation->getServerKeypairExpires())
                {
                    throw new RpcException('The server keypair has expired but the server has not provided a new keypair, contact the server administrator');
                }

                // Check if the transport encryption algorithm has changed
                if($this->serverInformation->getTransportEncryptionAlgorithm() !== $exportedSession->getTransportEncryptionAlgorithm())
                {
                    throw new RpcException('The server has changed its transport encryption algorithm, a new session must be created');
                }

                return;
            }

            // If the peer address is a string, we need to convert it to a PeerAddress object
            if(is_string($peerAddress))
            {
                $peerAddress = PeerAddress::fromAddress($peerAddress);
            }

            // Set the initial properties
            $this->peerAddress = $peerAddress;

            // Resolve the domain and get the server's Public Key & RPC Endpoint
            $resolvedServer = ServerResolver::resolveDomain($this->peerAddress->getDomain(), false);

            // Import the RPC Endpoint & the server's public key.
            $this->serverPublicSigningKey = $resolvedServer->getPublicSigningKey();
            $this->rpcEndpoint = $resolvedServer->getRpcEndpoint();

            if(empty($this->serverPublicSigningKey))
            {
                throw new ResolutionException('Failed to resolve domain: No public key found for the server');
            }

            // Resolve basic server information
            $this->serverInformation = self::getServerInformation();

            // Check if the server keypair has expired
            if($this->serverInformation->getServerKeypairExpires() > 0 && time() > $this->serverInformation->getServerKeypairExpires())
            {
                throw new RpcException('The server keypair has expired but the server has not provided a new keypair, contact the server administrator');
            }

            // If the username is `host` and the domain is the same as this server's domain, we use our signing keypair
            // Essentially this is a special case for the server to contact another server
            if($this->peerAddress->isHost())
            {
                $this->clientSigningKeyPair = new KeyPair(Configuration::getCryptographyConfiguration()->getHostPublicKey(), Configuration::getCryptographyConfiguration()->getHostPrivateKey());
            }
            // Otherwise we generate a random signing keypair
            else
            {
                $this->clientSigningKeyPair = Cryptography::generateSigningKeyPair();
            }

            // Always use a random encryption keypair
            $this->clientEncryptionKeyPair = Cryptography::generateEncryptionKeyPair();

            // Create a session with the server, with the method we obtain the Session UUID
            // And the server's public encryption key.
            $this->createSession();

            // Generate a transport encryption key on our end using the server's preferred algorithm
            $this->clientTransportEncryptionKey = Cryptography::generateEncryptionKey($this->serverInformation->getTransportEncryptionAlgorithm());

            // Preform the DHE so that transport encryption keys can be exchanged
            $this->sendDheExchange();
        }

        /**
         * Initiates a new session with the server and retrieves the session UUID.
         *
         * @return string The session UUID provided by the server upon successful session creation.
         * @throws RpcException If the session cannot be created, if the server does not provide a valid response,
         *                      or critical headers like encryption public key are missing in the server's response.
         */
        private function createSession(): void
        {
            $ch = curl_init();

            // Basic session details
            $headers = [
                StandardHeaders::REQUEST_TYPE->value . ': ' . RequestType::INITIATE_SESSION->value,
                StandardHeaders::CLIENT_NAME->value . ': ' . self::CLIENT_NAME,
                StandardHeaders::CLIENT_VERSION->value . ': ' . self::CLIENT_VERSION,
                StandardHeaders::IDENTIFY_AS->value . ': ' . $this->peerAddress->getAddress(),
                // Always provide our generated encrypted public key
                StandardHeaders::ENCRYPTION_PUBLIC_KEY->value . ': ' . $this->clientEncryptionKeyPair->getPublicKey()
            ];

            // If we're not connecting as the host, we need to provide our public key
            // Otherwise, the server will obtain the public key itself from DNS records rather than trusting the client
            if(!$this->peerAddress->isHost())
            {
                $headers[] = StandardHeaders::SIGNING_PUBLIC_KEY->value . ': ' . $this->clientSigningKeyPair->getPublicKey();
            }

            $responseHeaders = [];
            curl_setopt($ch, CURLOPT_URL, $this->rpcEndpoint);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // Capture the response headers to get the encryption public key
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$responseHeaders)
            {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                {
                    return $len;
                }

                $responseHeaders[strtolower(trim($header[0]))][] = trim($header[1]);
                return $len;
            });
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);

            // If the response is false, the request failed
            if($response === false)
            {
                curl_close($ch);
                throw new RpcException(sprintf('Failed to create the session at %s, no response received', $this->rpcEndpoint));
            }

            // If the response code is not 201, the request failed
            $responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            if($responseCode !== 201)
            {
                curl_close($ch);

                if(empty($response))
                {
                    throw new RpcException(sprintf('Failed to create the session at %s, server responded with ' . $responseCode, $this->rpcEndpoint));
                }

                throw new RpcException(sprintf('Failed to create the session at %s, server responded with ' . $responseCode . ': ' . $response, $this->rpcEndpoint));
            }

            // If the response is empty, the server did not provide a session UUID
            if(empty($response))
            {
                curl_close($ch);
                throw new RpcException(sprintf('Failed to create the session at %s, server did not return a session UUID', $this->rpcEndpoint));
            }

            // Get the Encryption Public Key from the server's response headers
            $serverPublicEncryptionKey = $responseHeaders[strtolower(StandardHeaders::ENCRYPTION_PUBLIC_KEY->value)][0] ?? null;

            // null check
            if($serverPublicEncryptionKey === null)
            {
                curl_close($ch);
                throw new RpcException('Failed to create session at %s, the server did not return a public encryption key');
            }

            // Validate the server's encryption public key
            if(!Cryptography::validatePublicEncryptionKey($serverPublicEncryptionKey))
            {
                curl_close($ch);
                throw new RpcException('The server did not provide a valid encryption public key');
            }

            // If the server did not provide an encryption public key, the response is invalid
            // We can't preform the DHE without the server's encryption key.
            if ($serverPublicEncryptionKey === null)
            {
                curl_close($ch);
                throw new RpcException('The server did not provide a signature for the response');
            }

            curl_close($ch);

            // Set the server's encryption key
            $this->serverPublicEncryptionKey = $serverPublicEncryptionKey;
            // Set the session UUID
            $this->sessionUuid = $response;
        }

        /**
         * Sends a Diffie-Hellman Ephemeral (DHE) exchange request to the server.
         *
         * @throws RpcException If the encryption or the request fails.
         */
        private function sendDheExchange(): void
        {
            // First preform the DHE
            try
            {
                $this->privateSharedSecret = Cryptography::performDHE($this->serverPublicEncryptionKey, $this->clientEncryptionKeyPair->getPrivateKey());
            }
            catch(CryptographyException $e)
            {
                throw new RpcException('Failed to preform DHE: ' . $e->getMessage(), StandardError::CRYPTOGRAPHIC_ERROR->value, $e);
            }

            // Request body should contain the encrypted key, the client's public key, and the session UUID
            // Upon success the server should return 204 without a body
            try
            {
                $encryptedKey = Cryptography::encryptShared($this->clientTransportEncryptionKey, $this->privateSharedSecret);
                $signature = Cryptography::signMessage($this->clientTransportEncryptionKey, $this->clientSigningKeyPair->getPrivateKey());
            }
            catch (CryptographyException $e)
            {
                throw new RpcException('Failed to encrypt DHE exchange data', StandardError::CRYPTOGRAPHIC_ERROR->value, $e);
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->rpcEndpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                StandardHeaders::REQUEST_TYPE->value . ': ' . RequestType::DHE_EXCHANGE->value,
                StandardHeaders::SESSION_UUID->value . ': ' . $this->sessionUuid,
                StandardHeaders::SIGNATURE->value . ': ' . $signature
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $encryptedKey);

            $response = curl_exec($ch);

            if($response === false)
            {
                curl_close($ch);
                throw new RpcException('Failed to send DHE exchange, no response received', StandardError::CRYPTOGRAPHIC_ERROR->value);
            }

            $responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            if($responseCode !== 200)
            {
                curl_close($ch);
                throw new RpcException('Failed to send DHE exchange, server responded with ' . $responseCode . ': ' . $response, StandardError::CRYPTOGRAPHIC_ERROR->value);
            }

            try
            {
                $this->serverTransportEncryptionKey = Cryptography::decryptShared($response, $this->privateSharedSecret);
            }
            catch(CryptographyException $e)
            {
                throw new RpcException('Failed to decrypt DHE exchange data', 0, $e);
            }
            finally
            {
                curl_close($ch);
            }
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
                $encryptedData = Cryptography::encryptMessage(
                    message: $jsonData,
                    encryptionKey: $this->serverTransportEncryptionKey,
                    algorithm: $this->serverInformation->getTransportEncryptionAlgorithm()
                );

                $signature = Cryptography::signMessage(
                    message: $jsonData,
                    privateKey: $this->clientSigningKeyPair->getPrivateKey(),
                );
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
                $decryptedResponse = Cryptography::decryptMessage(
                    encryptedMessage: $responseString,
                    encryptionKey: $this->clientTransportEncryptionKey,
                    algorithm: $this->serverInformation->getTransportEncryptionAlgorithm()
                );
            }
            catch (CryptographyException $e)
            {
                throw new RpcException('Failed to decrypt response: ' . $e->getMessage(), 0, $e);
            }

            if (!$this->bypassSignatureVerification)
            {
                $signature = $headers[strtolower(StandardHeaders::SIGNATURE->value)][0] ?? null;
                if ($signature === null)
                {
                    throw new RpcException('The server did not provide a signature for the response');
                }

                try
                {
                    if(!Cryptography::verifyMessage(
                        message: $decryptedResponse,
                        signature: $signature,
                        publicKey: $this->serverPublicSigningKey,
                    ))
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
         * Retrieves server information by making an RPC request.
         *
         * @return ServerInformation The parsed server information received in the response.
         * @throws RpcException If the request fails, no response is received, or the server returns an error status code or invalid data.
         */
        public function getServerInformation(): ServerInformation
        {
            $ch = curl_init();

            // Basic session details
            $headers = [
                StandardHeaders::REQUEST_TYPE->value . ': ' . RequestType::INFO->value,
                StandardHeaders::CLIENT_NAME->value . ': ' . self::CLIENT_NAME,
                StandardHeaders::CLIENT_VERSION->value . ': ' . self::CLIENT_VERSION,
            ];

            curl_setopt($ch, CURLOPT_URL, $this->rpcEndpoint);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($ch);

            if($response === false)
            {
                curl_close($ch);
                throw new RpcException(sprintf('Failed to get server information at %s, no response received', $this->rpcEndpoint));
            }

            $responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            if($responseCode !== 200)
            {
                curl_close($ch);

                if(empty($response))
                {
                    throw new RpcException(sprintf('Failed to get server information at %s, server responded with ' . $responseCode, $this->rpcEndpoint));
                }
            }

            curl_close($ch);
            return ServerInformation::fromArray(json_decode($response, true));
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
                'rpc_endpoint' => $this->rpcEndpoint,
                'session_uuid' => $this->sessionUuid,
                'transport_encryption_algorithm' => $this->serverInformation->getTransportEncryptionAlgorithm(),
                'server_keypair_expires' => $this->serverInformation->getServerKeypairExpires(),
                'server_public_signing_key' => $this->serverPublicSigningKey,
                'server_public_encryption_key' => $this->serverPublicEncryptionKey,
                'client_public_signing_key' => $this->clientSigningKeyPair->getPublicKey(),
                'client_private_signing_key' => $this->clientSigningKeyPair->getPrivateKey(),
                'client_public_encryption_key' => $this->clientEncryptionKeyPair->getPublicKey(),
                'client_private_encryption_key' => $this->clientEncryptionKeyPair->getPrivateKey(),
                'private_shared_secret' => $this->privateSharedSecret,
                'client_transport_encryption_key' => $this->clientTransportEncryptionKey,
                'server_transport_encryption_key' => $this->serverTransportEncryptionKey
            ]);
        }
    }