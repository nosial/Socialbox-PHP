<?php

    namespace Socialbox;

    use Exception;
    use InvalidArgumentException;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Classes\DnsHelper;
    use Socialbox\Classes\Logger;
    use Socialbox\Classes\ServerResolver;
    use Socialbox\Classes\Utilities;
    use Socialbox\Classes\Validator;
    use Socialbox\Enums\ReservedUsernames;
    use Socialbox\Enums\SessionState;
    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\StandardHeaders;
    use Socialbox\Enums\StandardMethods;
    use Socialbox\Enums\Types\RequestType;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\RequestException;
    use Socialbox\Exceptions\ResolutionException;
    use Socialbox\Exceptions\RpcException;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Managers\ExternalSessionManager;
    use Socialbox\Managers\RegisteredPeerManager;
    use Socialbox\Managers\SessionManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\Standard\Peer;
    use Socialbox\Objects\Standard\ServerInformation;
    use Throwable;

    class Socialbox
    {
        /**
         * Handles incoming client requests by parsing request headers, determining the request type,
         * and routing the request to the appropriate handler method. Implements error handling for
         * missing or invalid request types.
         *
         * @return void
         * @throws CryptographyException
         * @throws DatabaseOperationException
         * @throws ResolutionException
         */
        public static function handleRequest(): void
        {
            $requestHeaders = Utilities::getRequestHeaders();
            if(!isset($requestHeaders[StandardHeaders::REQUEST_TYPE->value]))
            {
                self::returnError(400, StandardError::BAD_REQUEST, 'Missing required header: ' . StandardHeaders::REQUEST_TYPE->value);
                return;
            }

            $clientRequest = new ClientRequest($requestHeaders, file_get_contents('php://input') ?? null);

            // Handle the request type, only `init` and `dhe` are not encrypted using the session's encrypted key
            // RPC Requests must be encrypted and signed by the client, vice versa for server responses.
            try
            {
                switch($clientRequest->getRequestType())
                {
                    case RequestType::PING:
                        self::handlePingRequest();
                        break;

                    case RequestType::INFO:
                        self::handleInformationRequest();
                        break;

                    case RequestType::INITIATE_SESSION:
                        self::handleInitiateSession($clientRequest);
                        break;

                    case RequestType::DHE_EXCHANGE:
                        self::handleDheExchange($clientRequest);
                        break;

                    case RequestType::RPC:
                        self::handleRpc($clientRequest);
                        break;

                    default:
                        self::returnError(400, StandardError::BAD_REQUEST, 'Invalid Request-Type header');
                }
            }
            catch(Exception $e)
            {
                self::returnError(500, StandardError::INTERNAL_SERVER_ERROR, 'An internal error occurred while processing the request', $e);
            }

        }

        /**
         * Handles an incoming ping request by sending a successful HTTP response.
         *
         * @return void
         */
        private static function handlePingRequest(): void
        {
            http_response_code(200);
            header('Content-Type: text/plain');
            print('OK');
        }

        /**
         * Handles an information request by setting the appropriate HTTP response code,
         * content type headers, and printing the server information in JSON format.
         *
         * @return void
         */
        private static function handleInformationRequest(): void
        {
            http_response_code(200);
            header('Content-Type: application/json');
            Logger::getLogger()->info(json_encode(self::getServerInformation()->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            print(json_encode(self::getServerInformation()->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        /**
         * Validates the initial headers of a client request to ensure all required headers exist
         * and contain valid values. If any validation fails, an error response is returned.
         *
         * @param ClientRequest $clientRequest The client request containing headers to be validated.
         * @return bool Returns true if all required headers are valid, otherwise false.
         */
        private static function validateInitHeaders(ClientRequest $clientRequest): bool
        {
            if(!$clientRequest->getClientName())
            {
                self::returnError(400, StandardError::BAD_REQUEST, 'Missing required header: ' . StandardHeaders::CLIENT_NAME->value);
                return false;
            }

            if(!$clientRequest->getClientVersion())
            {
                self::returnError(400, StandardError::BAD_REQUEST, 'Missing required header: ' . StandardHeaders::CLIENT_VERSION->value);
                return false;
            }

            if(!$clientRequest->headerExists(StandardHeaders::ENCRYPTION_PUBLIC_KEY))
            {
                self::returnError(400, StandardError::BAD_REQUEST, 'Missing required header: ' . StandardHeaders::ENCRYPTION_PUBLIC_KEY->value);
                return false;
            }

            if(!$clientRequest->headerExists(StandardHeaders::IDENTIFY_AS))
            {
                self::returnError(400, StandardError::BAD_REQUEST, 'Missing required header: ' . StandardHeaders::IDENTIFY_AS->value);
                return false;
            }

            if(!Validator::validatePeerAddress($clientRequest->getHeader(StandardHeaders::IDENTIFY_AS)))
            {
                self::returnError(400, StandardError::BAD_REQUEST, 'Invalid Identify-As header: ' . $clientRequest->getHeader(StandardHeaders::IDENTIFY_AS));
                return false;
            }

            if(!$clientRequest->getIdentifyAs()->isExternal() && !$clientRequest->headerExists(StandardHeaders::SIGNING_PUBLIC_KEY))
            {
                self::returnError(400, StandardError::BAD_REQUEST, 'Missing required header: ' . StandardHeaders::SIGNING_PUBLIC_KEY->value);
                return false;
            }

            return true;
        }

        /**
         * Handles the initiation of a session for a client request. This involves validating headers,
         * verifying peer identities, resolving domains, registering peers if necessary, and finally
         * creating a session while providing the required session UUID as a response.
         *
         * @param ClientRequest $clientRequest The incoming client request containing all necessary headers
         *                                      and identification information required to initiate the session.
         * @return void
         */
        private static function handleInitiateSession(ClientRequest $clientRequest): void
        {
            // This is only called for the `init` request type
            if(!self::validateInitHeaders($clientRequest))
            {
                return;
            }

            // We always accept the client's public key at first
            $clientPublicSigningKey = $clientRequest->getHeader(StandardHeaders::SIGNING_PUBLIC_KEY);
            $clientPublicEncryptionKey = $clientRequest->getHeader(StandardHeaders::ENCRYPTION_PUBLIC_KEY);

            // If the peer is identifying as the same domain
            if($clientRequest->getIdentifyAs()->getDomain() === Configuration::getInstanceConfiguration()->getDomain())
            {
                // Prevent the peer from identifying as the host unless it's coming from an external domain
               if($clientRequest->getIdentifyAs()->getUsername() === ReservedUsernames::HOST->value)
               {
                   self::returnError(403, StandardError::FORBIDDEN, 'Unauthorized: Not allowed to identify as the host');
                   return;
               }
            }
            // If the peer is identifying as an external domain
            else
            {
                // Only allow the host to identify as a host
                if($clientRequest->getIdentifyAs()->getUsername() !== ReservedUsernames::HOST->value)
                {
                    self::returnError(403, StandardError::FORBIDDEN, 'Forbidden: Any external peer must identify as the host, only the host can preform actions on behalf of it\'s peers');
                    return;
                }

                try
                {
                    // We need to obtain the public key of the host, since we can't trust the client (Use database)
                    $resolvedServer = ServerResolver::resolveDomain($clientRequest->getIdentifyAs()->getDomain());

                    // Override the public signing key with the resolved server's public key
                    // Encryption key can be left as is.
                    $clientPublicSigningKey = $resolvedServer->getPublicSigningKey();
                }
                catch (Exception $e)
                {
                    self::returnError(502, StandardError::RESOLUTION_FAILED, 'Conflict: Failed to resolve the host domain: ' . $e->getMessage(), $e);
                    return;
                }
            }

            try
            {
                // Check if we have a registered peer with the same address
                $registeredPeer = RegisteredPeerManager::getPeerByAddress($clientRequest->getIdentifyAs());

                // If the peer is registered, check if it is enabled
                if($registeredPeer !== null && !$registeredPeer->isEnabled())
                {
                    // Refuse to create a session if the peer is disabled/banned, this usually happens when
                    // a peer gets banned or more commonly when a client attempts to register as this peer but
                    // destroyed the session before it was created.
                    // This is to prevent multiple sessions from being created for the same peer, this is cleaned up
                    // with a cron job using `socialbox clean-sessions`
                    self::returnError(403, StandardError::FORBIDDEN, 'Unauthorized: The requested peer is disabled/banned');
                    return;
                }
                // If-clause for handling the host peer, host peers are always enabled unless the fist clause is true
                // in which case the host was blocked by this server.
                elseif($clientRequest->getIdentifyAs() === ReservedUsernames::HOST->value)
                {
                    $serverInformation = self::getExternalServerInformation($clientRequest->getIdentifyAs()->getDomain());

                    // If the host is not registered, register it
                    if($registeredPeer === null)
                    {
                        $peerUuid = RegisteredPeerManager::createPeer(PeerAddress::fromAddress($clientRequest->getHeader(StandardHeaders::IDENTIFY_AS)));
                        RegisteredPeerManager::updateDisplayName($peerUuid, $serverInformation->getServerName());
                        RegisteredPeerManager::enablePeer($peerUuid);
                    }
                    // Otherwise, update the display name if it has changed
                    else
                    {
                        RegisteredPeerManager::updateDisplayName($registeredPeer->getUuid(), $serverInformation->getServerName());
                    }
                }
                // Otherwise the peer isn't registered, so we need to register it
                else
                {
                    // Check if registration is enabled
                    if(!Configuration::getRegistrationConfiguration()->isRegistrationEnabled())
                    {
                        self::returnError(401, StandardError::UNAUTHORIZED, 'Unauthorized: Registration is disabled');
                        return;
                    }

                    // Register the peer if it is not already registered
                    $peerUuid = RegisteredPeerManager::createPeer(PeerAddress::fromAddress($clientRequest->getHeader(StandardHeaders::IDENTIFY_AS)));
                    // Retrieve the peer object
                    $registeredPeer = RegisteredPeerManager::getPeer($peerUuid);
                }

                // Generate server's encryption keys for this session
                $serverEncryptionKey = Cryptography::generateEncryptionKeyPair();

                // Create the session passing on the registered peer, client name, version, and public keys
                $sessionUuid = SessionManager::createSession($registeredPeer, $clientRequest->getClientName(), $clientRequest->getClientVersion(), $clientPublicSigningKey, $clientPublicEncryptionKey, $serverEncryptionKey);

                // The server responds back with the session UUID & The server's public encryption key as the header
                http_response_code(201); // Created
                header('Content-Type: text/plain');
                header(StandardHeaders::ENCRYPTION_PUBLIC_KEY->value . ': ' . $serverEncryptionKey->getPublicKey());
                print($sessionUuid); // Return the session UUID
            }
            catch(InvalidArgumentException $e)
            {
                // This is usually thrown due to an invalid input
                self::returnError(400, StandardError::BAD_REQUEST, $e->getMessage(), $e);
            }
            catch(Exception $e)
            {
                self::returnError(500, StandardError::INTERNAL_SERVER_ERROR, 'An internal error occurred while initiating the session', $e);
            }
        }

        /**
         * Handles the Diffie-Hellman Ephemeral (DHE) key exchange process between the client and server,
         * ensuring secure transport encryption key negotiation. The method validates request headers,
         * session state, and cryptographic operations, and updates the session with the resulting keys
         * and state upon successful negotiation.
         *
         * @param ClientRequest $clientRequest The request object containing headers, body, and session details
         *                                     required to perform the DHE exchange.
         *
         * @return void
         * @throws CryptographyException
         */
        private static function handleDheExchange(ClientRequest $clientRequest): void
        {
            // Check if the session UUID is set in the headers, bad request if not
            if(!$clientRequest->headerExists(StandardHeaders::SESSION_UUID))
            {
                self::returnError(400, StandardError::BAD_REQUEST, 'Missing required header: ' . StandardHeaders::SESSION_UUID->value);
                return;
            }

            if(!$clientRequest->headerExists(StandardHeaders::SIGNATURE))
            {
                self::returnError(400, StandardError::BAD_REQUEST, 'Missing required header: ' . StandardHeaders::SIGNATURE->value);
                return;
            }

            if(empty($clientRequest->getHeader(StandardHeaders::SIGNATURE)))
            {
                self::returnError(400, StandardError::BAD_REQUEST, 'Bad request: The signature is empty');
                return;
            }

            // Check if the request body is empty, bad request if so
            if(empty($clientRequest->getRequestBody()))
            {
                self::returnError(400, StandardError::BAD_REQUEST, 'Bad request: The key exchange request body is empty');
                return;
            }

            $session = $clientRequest->getSession();
            if($session === null)
            {
                self::returnError(404, StandardError::SESSION_NOT_FOUND, 'Session not found');
                return;
            }

            // Check if the session is awaiting a DHE exchange, forbidden if not
            if($session->getState() !== SessionState::AWAITING_DHE)
            {
                self::returnError(403, StandardError::FORBIDDEN, 'Bad request: The session is not awaiting a DHE exchange');
                return;
            }


            // DHE STAGE: CLIENT -> SERVER
            // Server & Client: Begin the DHE exchange using the exchanged public keys.
            // On the client's side, same method but with the server's public key & client's private key
            try
            {
                $sharedSecret = Cryptography::performDHE($session->getClientPublicEncryptionKey(), $session->getServerPrivateEncryptionKey());
            }
            catch (CryptographyException $e)
            {
                Logger::getLogger()->error('Failed to perform DHE exchange', $e);
                self::returnError(422, StandardError::CRYPTOGRAPHIC_ERROR, 'DHE exchange failed', $e);
                return;
            }

            // STAGE 1: CLIENT -> SERVER
            try
            {
                // Attempt to decrypt the encrypted key passed on from the client using the shared secret
                $clientTransportEncryptionKey = Cryptography::decryptShared($clientRequest->getRequestBody(), $sharedSecret);
            }
            catch (CryptographyException $e)
            {
                self::returnError(400, StandardError::CRYPTOGRAPHIC_ERROR, 'Failed to decrypt the key', $e);
                return;
            }

            // Get the signature from the client and validate it against the decrypted key
            $clientSignature = $clientRequest->getHeader(StandardHeaders::SIGNATURE);
            if(!Cryptography::verifyMessage($clientTransportEncryptionKey, $clientSignature, $session->getClientPublicSigningKey()))
            {
                self::returnError(401, StandardError::UNAUTHORIZED, 'Invalid signature');
                return;
            }

            // Validate the encryption key given by the client
            if(!Cryptography::validateEncryptionKey($clientTransportEncryptionKey, Configuration::getCryptographyConfiguration()->getTransportEncryptionAlgorithm()))
            {
                self::returnError(400, StandardError::BAD_REQUEST, 'The transport encryption key is invalid and does not meet the server\'s requirements');
                return;
            }

            // Receive stage complete, now we move on to the server's response

            // STAGE 2: SERVER -> CLIENT
            try
            {
                // Generate the server's transport encryption key (our side)
                $serverTransportEncryptionKey = Cryptography::generateEncryptionKey(Configuration::getCryptographyConfiguration()->getTransportEncryptionAlgorithm());

                // Sign the shared secret using the server's private key
                $signature = Cryptography::signMessage($serverTransportEncryptionKey, Configuration::getCryptographyConfiguration()->getHostPrivateKey());
                // Encrypt the server's transport key using the shared secret
                $encryptedServerTransportKey = Cryptography::encryptShared($serverTransportEncryptionKey, $sharedSecret);
            }
            catch (CryptographyException $e)
            {
                Logger::getLogger()->error('Failed to generate the server\'s transport encryption key', $e);
                self::returnError(500, StandardError::INTERNAL_SERVER_ERROR, 'There was an error while trying to process the DHE exchange', $e);
                return;
            }

            // Now update the session details with all the encryption keys and the state
            try
            {
                SessionManager::setEncryptionKeys($clientRequest->getSessionUuid(), $sharedSecret, $clientTransportEncryptionKey, $serverTransportEncryptionKey);
                SessionManager::updateState($clientRequest->getSessionUuid(), SessionState::ACTIVE);
            }
            catch (DatabaseOperationException $e)
            {
                Logger::getLogger()->error('Failed to set the encryption key for the session', $e);
                self::returnError(500, StandardError::INTERNAL_SERVER_ERROR, 'Failed to set the encryption key for the session', $e);
                return;
            }

            // Return the encrypted transport key for the server back to the client.
            http_response_code(200);
            header('Content-Type: application/octet-stream');
            header(StandardHeaders::SIGNATURE->value . ': ' . $signature);
            print($encryptedServerTransportKey);
        }

        /**
         * Handles a Remote Procedure Call (RPC) request, ensuring proper decryption,
         * signature verification, and response encryption, while processing one or more
         * RPC methods as specified in the request.
         *
         * @param ClientRequest $clientRequest The RPC client request containing headers, body, and session information.
         *
         * @return void
         * @throws CryptographyException
         * @throws DatabaseOperationException
         * @throws ResolutionException
         */
        private static function handleRpc(ClientRequest $clientRequest): void
        {
            // Client: Encrypt the request body using the server's encryption key & sign it using the client's private key
            // Server: Decrypt the request body using the servers's encryption key & verify the signature using the client's public key
            // Server: Encrypt the response using the client's encryption key & sign it using the server's private key

            if(!$clientRequest->headerExists(StandardHeaders::SESSION_UUID))
            {
                self::returnError(400, StandardError::BAD_REQUEST, 'Missing required header: ' . StandardHeaders::SESSION_UUID->value);
                return;
            }

            if(!$clientRequest->headerExists(StandardHeaders::SIGNATURE))
            {
                self::returnError(400, StandardError::BAD_REQUEST, 'Missing required header: ' . StandardHeaders::SIGNATURE->value);
                return;
            }

            // Get the client session
            $session = $clientRequest->getSession();

            // Verify if the session is active
            if($session->getState() !== SessionState::ACTIVE)
            {
                self::returnError(403, StandardError::FORBIDDEN, 'Session is not active (' . $session->getState()->value . ')');
                return;
            }

            try
            {
                SessionManager::updateLastRequest($session->getUuid());
            }
            catch (DatabaseOperationException $e)
            {
                Logger::getLogger()->error('Failed to update the last request time for the session', $e);
                self::returnError(500, StandardError::INTERNAL_SERVER_ERROR, 'Failed to update the session', $e);
                return;
            }

            try
            {
                // Attempt to decrypt the request body using the server's encryption key
                $decryptedContent = Cryptography::decryptMessage($clientRequest->getRequestBody(), $session->getServerTransportEncryptionKey(), Configuration::getCryptographyConfiguration()->getTransportEncryptionAlgorithm());
            }
            catch(CryptographyException $e)
            {
                self::returnError(400, StandardError::CRYPTOGRAPHIC_ERROR, 'Failed to decrypt request', $e);
                return;
            }

            // Attempt to verify the decrypted content using the client's public signing key
            if(!Cryptography::verifyMessage($decryptedContent, $clientRequest->getSignature(), $session->getClientPublicSigningKey()))
            {
                self::returnError(400, StandardError::CRYPTOGRAPHIC_ERROR, 'Signature verification failed');
                return;
            }

            // If the client has provided an identification header, further validation and resolution is required
            if($clientRequest->getIdentifyAs() !== null)
            {
                // First check if the client is identifying as the host
                if($clientRequest->getPeer()->getAddress() !== ReservedUsernames::HOST->value)
                {
                    // TODO: Maybe allow user client to change identification but within an RPC method rather than the headers
                    self::returnError(403, StandardError::FORBIDDEN, 'Unauthorized: Not allowed to identify as a different peer');
                    return;
                }

                // Synchronize the peer
                try
                {
                    self::synchronizeExternalPeer($clientRequest->getIdentifyAs());
                }
                catch (DatabaseOperationException $e)
                {
                    self::returnError(500, StandardError::INTERNAL_SERVER_ERROR, 'Failed to synchronize external peer', $e);
                    return;
                }
                catch (Exception $e)
                {
                    throw new ResolutionException(sprintf('Failed to synchronize external peer %s: %s', $clientRequest->getIdentifyAs()->getAddress(), $e->getMessage()), $e->getCode(), $e);
                }
            }

            try
            {
                $clientRequests = $clientRequest->getRpcRequests($decryptedContent);
            }
            catch (RequestException $e)
            {
                self::returnError($e->getCode(), $e->getStandardError(), $e->getMessage());
                return;
            }

            Logger::getLogger()->verbose(sprintf('Received %d RPC request(s) from %s', count($clientRequests), $_SERVER['REMOTE_ADDR']));

            $results = [];
            foreach($clientRequests as $rpcRequest)
            {
                $method = StandardMethods::tryFrom($rpcRequest->getMethod());

                try
                {
                    $method->checkAccess($clientRequest);
                }
                catch (StandardException $e)
                {
                    $response = $e->produceError($rpcRequest);
                    $results[] = $response->toArray();
                    continue;
                }

                if($method === false)
                {
                    Logger::getLogger()->warning('The requested method does not exist');
                    $response = $rpcRequest->produceError(StandardError::RPC_METHOD_NOT_FOUND, 'The requested method does not exist');
                }
                else
                {
                    try
                    {
                        Logger::getLogger()->debug(sprintf('Processing RPC request for method %s', $rpcRequest->getMethod()));
                        $response = $method->execute($clientRequest, $rpcRequest);
                        Logger::getLogger()->debug(sprintf('%s method executed successfully', $rpcRequest->getMethod()));
                    }
                    catch(StandardException $e)
                    {
                        Logger::getLogger()->error('An error occurred while processing the RPC request', $e);
                        $response = $e->produceError($rpcRequest);
                    }
                    catch(Exception $e)
                    {
                        Logger::getLogger()->error('An internal error occurred while processing the RPC request', $e);
                        if(Configuration::getSecurityConfiguration()->isDisplayInternalExceptions())
                        {
                            $response = $rpcRequest->produceError(StandardError::INTERNAL_SERVER_ERROR, Utilities::throwableToString($e));
                        }
                        else
                        {
                            $response = $rpcRequest->produceError(StandardError::INTERNAL_SERVER_ERROR);
                        }
                    }
                }

                if($response !== null)
                {
                    Logger::getLogger()->debug(sprintf('Producing response for method %s', $rpcRequest->getMethod()));
                    $results[] = $response->toArray();
                }
            }

            $response = null;

            if(count($results) == 0)
            {
                $response = null;
            }
            elseif(count($results) == 1)
            {
                $response = json_encode($results[0], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            else
            {
                $response = json_encode($results, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            if($response === null)
            {
                http_response_code(204);
                return;
            }

            $session = $clientRequest->getSession();

            try
            {
                $encryptedResponse = Cryptography::encryptMessage(
                    message: $response,
                    encryptionKey: $session->getClientTransportEncryptionKey(),
                    algorithm: Configuration::getCryptographyConfiguration()->getTransportEncryptionAlgorithm()
                );

                $signature = Cryptography::signMessage(
                    message: $response,
                    privateKey: Configuration::getCryptographyConfiguration()->getHostPrivateKey()
                );
            }
            catch (Exception $e)
            {
                self::returnError(500, StandardError::INTERNAL_SERVER_ERROR, 'Failed to encrypt the server response', $e);
                return;
            }

            http_response_code(200);
            header('Content-Type: application/octet-stream');
            header(StandardHeaders::SIGNATURE->value . ': ' . $signature);
            print($encryptedResponse);
        }

        /**
         * Sends an error response by setting the HTTP response code, headers, and printing an error message.
         * Optionally includes exception details in the response if enabled in the configuration.
         * Logs the error message and any associated exception.
         *
         * @param int $responseCode The HTTP response code to send.
         * @param StandardError $standardError The standard error containing error details.
         * @param string|null $message An optional error message to display. Defaults to the message from the StandardError instance.
         * @param Throwable|null $e An optional throwable to include in logs and the response, if enabled.
         *
         * @return void
         */
        private static function returnError(int $responseCode, StandardError $standardError, ?string $message=null, ?Throwable $e=null): void
        {
            if($message === null)
            {
                $message = $standardError->getMessage();
            }

            http_response_code($responseCode);
            header('Content-Type: text/plain');
            header(StandardHeaders::ERROR_CODE->value . ': ' . $standardError->value);
            print($message);

            if(Configuration::getSecurityConfiguration()->isDisplayInternalExceptions() && $e !== null)
            {
                print(PHP_EOL . PHP_EOL . Utilities::throwableToString($e));
            }

            if($e !== null)
            {
                Logger::getLogger()->error($message, $e);
            }
        }

        /**
         * Retrieves an external session associated with the given domain.
         *
         * If a session already exists for the specified domain, it retrieves and uses the existing session.
         * Otherwise, it establishes a new connection, creates a session, and stores it for later use.
         *
         * @param string $domain The domain for which the external session is to be retrieved.
         * @return SocialClient The RPC client initialized with the external session for the given domain.
         * @throws CryptographyException If there was an error in the cryptography
         * @throws DatabaseOperationException If there was an error while processing the session against the database
         * @throws ResolutionException If the connection to the remote server fails.
         * @throws RpcException If there is an RPC exception while connecting to the remote server
         */
        public static function getExternalSession(string $domain): SocialClient
        {
            if(ExternalSessionManager::sessionExists($domain))
            {
                return new SocialClient(self::getServerAddress(), $domain, ExternalSessionManager::getSession($domain));
            }

            try
            {
                $client = new SocialClient(self::getServerAddress(), $domain);
                $client->authenticate();
            }
            catch (Exception $e)
            {
                throw new ResolutionException(sprintf('Failed to connect to remote server %s: %s', $domain, $e->getMessage()), $e->getCode(), $e);
            }

            ExternalSessionManager::addSession($client->exportSession());
            return $client;
        }

        /**
         * Retrieves external server information for the specified domain.
         *
         * @param string $domain The domain from which the server information is to be retrieved.
         * @return ServerInformation The server information retrieved from the external session.
         * @throws ResolutionException If unable to retrieve server information for the given domain.
         */
        public static function getExternalServerInformation(string $domain): ServerInformation
        {
            try
            {
                return self::getExternalSession($domain)->getServerInformation();
            }
            catch (Exception $e)
            {
                throw new ResolutionException(sprintf('Failed to retrieve server information from %s: %s', $domain, $e->getMessage()), $e->getCode(), $e);
            }
        }

        /**
         * Synchronizes an external peer by resolving and integrating its information into the system.
         *
         * @param PeerAddress|Peer|string $externalPeer The external peer to synchronize, provided as a PeerAddress instance or a string.
         * @return void
         * @throws CryptographyException If there is an error in the cryptography
         * @throws DatabaseOperationException If there is an error while processing the peer against the database
         * @throws ResolutionException If the synchronization process fails due to unresolved peer information or other errors.
         * @throws RpcException If there is an RPC exception while connecting to the remote server
         */
        public static function synchronizeExternalPeer(PeerAddress|Peer|string $externalPeer): void
        {
            if($externalPeer instanceof Peer)
            {
                RegisteredPeerManager::synchronizeExternalPeer($externalPeer);
                return;
            }

            if($externalPeer instanceof PeerAddress)
            {
                $externalPeer = $externalPeer->getAddress();
            }

            $client = self::getExternalSession($externalPeer->getDomain());
            RegisteredPeerManager::synchronizeExternalPeer($client->resolvePeer($externalPeer));
        }

        /**
         * Resolves an external peer based on the given peer address or string identifier.
         *
         * @param PeerAddress|string $externalPeer The external peer address or string identifier to be resolved.
         * @return Peer The resolved external peer after synchronization.
         */
        public static function resolveExternalPeer(PeerAddress|string $externalPeer): Peer
        {
            if($externalPeer instanceof PeerAddress)
            {
                $externalPeer = $externalPeer->getAddress();
            }

            $resolvedPeer = self::getExternalSession($externalPeer->getDomain())->resolvePeer($externalPeer);
            self::synchronizeExternalPeer($resolvedPeer);
            return $resolvedPeer;
        }

        /**
         * Retrieves the server information by assembling data from the configuration settings.
         *
         * @return ServerInformation An instance of ServerInformation containing details such as server name, hashing algorithm,
         * transport AES mode, and AES key length.
         */
        public static function getServerInformation(): ServerInformation
        {
            return ServerInformation::fromArray([
                'server_name' => Configuration::getInstanceConfiguration()->getName(),
                'server_keypair_expires' => Configuration::getCryptographyConfiguration()->getHostKeyPairExpires(),
                'transport_encryption_algorithm' => Configuration::getCryptographyConfiguration()->getTransportEncryptionAlgorithm()
            ]);
        }

        /**
         * Retrieves the server address.
         *
         * @return PeerAddress The constructed server address containing the host and domain information.
         */
        public static function getServerAddress(): PeerAddress
        {
            return new PeerAddress(ReservedUsernames::HOST->value, Configuration::getInstanceConfiguration()->getDomain());
        }

        /**
         * Retrieves the DNS record by generating a TXT record using the RPC endpoint,
         * host public key, and host key pair expiration from the configuration.
         *
         * @return string The generated DNS TXT record.
         */
        public static function getDnsRecord(): string
        {
            return DnsHelper::generateTxt(
                Configuration::getInstanceConfiguration()->getRpcEndpoint(),
                Configuration::getCryptographyConfiguration()->getHostPublicKey(),
                Configuration::getCryptographyConfiguration()->getHostKeyPairExpires()
            );
        }
    }