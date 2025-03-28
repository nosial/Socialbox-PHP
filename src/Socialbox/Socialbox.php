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
    use Socialbox\Enums\Flags\PeerFlags;
    use Socialbox\Enums\PrivacyState;
    use Socialbox\Enums\ReservedUsernames;
    use Socialbox\Enums\SessionState;
    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\StandardHeaders;
    use Socialbox\Enums\StandardMethods;
    use Socialbox\Enums\Status\SignatureVerificationStatus;
    use Socialbox\Enums\Types\ContactRelationshipType;
    use Socialbox\Enums\Types\InformationFieldName;
    use Socialbox\Enums\Types\RequestType;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\RequestException;
    use Socialbox\Exceptions\ResolutionException;
    use Socialbox\Exceptions\RpcException;
    use Socialbox\Exceptions\Standard\InvalidRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Managers\ContactManager;
    use Socialbox\Managers\ExternalSessionManager;
    use Socialbox\Managers\PeerInformationManager;
    use Socialbox\Managers\RegisteredPeerManager;
    use Socialbox\Managers\SessionManager;
    use Socialbox\Managers\SigningKeysManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\Standard\InformationField;
    use Socialbox\Objects\Standard\Peer;
    use Socialbox\Objects\Standard\ServerInformation;
    use Socialbox\Objects\Standard\Signature;
    use Throwable;

    class Socialbox
    {
        /**
         * Handles incoming client requests by parsing request headers, determining the request type,
         * and routing the request to the appropriate handler method. Implements error handling for
         * missing or invalid request types.
         *
         * @return void
         */
        public static function handleRequest(): void
        {
            $requestHeaders = Utilities::getRequestHeaders();
            if(!isset($requestHeaders[StandardHeaders::REQUEST_TYPE->value]))
            {
                self::returnError(400, StandardError::BAD_REQUEST, 'Missing required header: ' . StandardHeaders::REQUEST_TYPE->value);
                return;
            }

            Logger::getLogger()->debug('Received request from ' . $_SERVER['REMOTE_ADDR']);
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
                   self::returnError(403, StandardError::FORBIDDEN, 'Forbidden: Not allowed to identify as the host');
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
                    self::returnError(502, StandardError::RESOLUTION_FAILED, 'Conflict: Failed to resolve incoming host, ' . $e->getMessage(), $e);
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
                if($clientRequest->getIdentifyAs()->getUsername() === ReservedUsernames::HOST->value && $registeredPeer === null)
                {
                    $peerUuid = RegisteredPeerManager::createPeer($clientRequest->getIdentifyAs());
                    RegisteredPeerManager::enablePeer($peerUuid);
                    $registeredPeer = RegisteredPeerManager::getPeer($peerUuid);
                }

                if($registeredPeer === null)
                {
                    // Check if registration is enabled
                    if(!Configuration::getRegistrationConfiguration()->isRegistrationEnabled())
                    {
                        self::returnError(401, StandardError::UNAUTHORIZED, 'Unauthorized: Registration is disabled');
                        return;
                    }

                    // Register the peer if it is not already registered
                    $peerUuid = RegisteredPeerManager::createPeer($clientRequest->getIdentifyAs());
                    // Retrieve the peer object
                    $registeredPeer = RegisteredPeerManager::getPeer($peerUuid);
                }

                // Generate server's encryption keys for this session
                $serverEncryptionKeyPair = Cryptography::generateEncryptionKeyPair();

                // Create the session passing on the registered peer, client name, version, and public keys
                $sessionUuid = SessionManager::createSession(
                    peer: $registeredPeer,
                    clientName: $clientRequest->getClientName(),
                    clientVersion: $clientRequest->getClientVersion(),
                    clientPublicSigningKey: $clientPublicSigningKey,
                    clientPublicEncryptionKey: $clientPublicEncryptionKey,
                    serverEncryptionKeyPair: $serverEncryptionKeyPair
                );
            }
            catch(InvalidArgumentException $e)
            {
                // This is usually thrown due to an invalid input
                self::returnError(400, StandardError::BAD_REQUEST, $e->getMessage(), $e);
                return;
            }
            catch(Exception $e)
            {
                self::returnError(500, StandardError::INTERNAL_SERVER_ERROR, 'An internal error occurred while initiating the session', $e);
                return;
            }

            // The server responds back with the session UUID & The server's public encryption key as the header
            http_response_code(201); // Created
            header('Content-Type: text/plain');
            header(StandardHeaders::ENCRYPTION_PUBLIC_KEY->value . ': ' . $serverEncryptionKeyPair->getPublicKey());
            print($sessionUuid); // Return the session UUID
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

            try
            {
                $session = $clientRequest->getSession();
            }
            catch (DatabaseOperationException $e)
            {
                self::returnError(500, StandardError::INTERNAL_SERVER_ERROR, 'Failed to retrieve session', $e);
                return;
            }

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
                try
                {
                    $hostPeer = $clientRequest->getPeer();
                }
                catch (DatabaseOperationException $e)
                {
                    self::returnError(500, StandardError::INTERNAL_SERVER_ERROR, 'Failed to resolve host peer', $e);
                    return;
                }

                // If for some reason the host peer was not found, this shouldn't happen.
                if($hostPeer === null)
                {
                    self::returnError(404, StandardError::INTERNAL_SERVER_ERROR, 'The host peer was not found in the system');
                    return;
                }
                // First check if the client is identifying as the host
                elseif($hostPeer->getUsername() !== ReservedUsernames::HOST->value)
                {
                    self::returnError(403, StandardError::FORBIDDEN, 'Cannot identify as a peer when not identifying as the host');
                    return;
                }
                // Secondly, check if the peer's server belongs to another server than the server is identified as
                elseif($hostPeer->getServer() !== $clientRequest->getIdentifyAs()->getDomain())
                {
                    self::returnError(403, StandardError::FORBIDDEN, 'Not allowed to identify as a peer outside from the host server');
                    return;
                }

                // Synchronize the peer
                try
                {
                    self::resolvePeer($clientRequest->getIdentifyAs());
                }
                catch (StandardRpcException $e)
                {
                    throw new ResolutionException(sprintf('Failed to resolve peer %s: %s', $clientRequest->getIdentifyAs()->getAddress(), $e->getMessage()), $e->getCode(), $e);
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

            if(count($clientRequests) === 0)
            {
                Logger::getLogger()->warning(sprintf('Received no RPC requests from %s', $_SERVER['REMOTE_ADDR']));
                http_response_code(204);
                return;
            }

            Logger::getLogger()->verbose(sprintf('Received %d RPC request(s) from %s', count($clientRequests), $_SERVER['REMOTE_ADDR']));

            $results = [];
            foreach($clientRequests as $rpcRequest)
            {
                $method = StandardMethods::tryFrom($rpcRequest->getMethod());
                if($method === false)
                {
                    Logger::getLogger()->warning('The requested method does not exist');
                    $results[] = $rpcRequest->produceError(StandardError::RPC_METHOD_NOT_FOUND, 'The requested method does not exist');
                    continue;
                }

                try
                {
                    if (!$method->checkAccess($clientRequest))
                    {
                        $results[] = $rpcRequest->produceError(StandardError::METHOD_NOT_ALLOWED, 'Insufficient access requirements to invoke the session');
                        continue;
                    }
                }
                catch (DatabaseOperationException $e)
                {
                    Logger::getLogger()->error('Failed to check method access', $e);
                    $results[] = $rpcRequest->produceError(StandardError::INTERNAL_SERVER_ERROR, 'Failed to check method access due to an internal server error');
                    continue;
                }
                catch (StandardRpcException $e)
                {
                    $results[] = $e->produceError($rpcRequest);
                    continue;
                }

                try
                {
                    Logger::getLogger()->debug(sprintf('Processing RPC request for method %s', $rpcRequest->getMethod()));
                    $results[] = $method->execute($clientRequest, $rpcRequest);
                    Logger::getLogger()->debug(sprintf('%s method executed successfully', $rpcRequest->getMethod()));
                }
                catch(StandardRpcException $e)
                {
                    Logger::getLogger()->error('An error occurred while processing the RPC request', $e);
                    $results[] = $e->produceError($rpcRequest);
                }
                catch(InvalidArgumentException $e)
                {
                    Logger::getLogger()->error('Caught invalid argument exception', $e);
                    $results[] = $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, $e->getMessage());
                }
                catch(Exception $e)
                {
                    Logger::getLogger()->error('An internal error occurred while processing the RPC request', $e);
                    if(Configuration::getSecurityConfiguration()->isDisplayInternalExceptions())
                    {
                        $results[] = $rpcRequest->produceError(StandardError::INTERNAL_SERVER_ERROR, Utilities::throwableToString($e));
                    }
                    else
                    {
                        $results[] = $rpcRequest->produceError(StandardError::INTERNAL_SERVER_ERROR, 'Uncaught Exception');
                    }
                }
            }

            $results = array_map(fn($result) => $result->toArray(), $results);
            if(count($results) == 0)
            {
                http_response_code(204);
                return;
            }
            elseif(count($results) == 1)
            {
                $response = json_encode($results[0], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            else
            {
                $response = json_encode($results, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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
            if($e !== null)
            {
                Logger::getLogger()->error($message, $e);
            }

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
            $domain = strtolower($domain);

            if(ExternalSessionManager::sessionExists($domain))
            {
                return new SocialClient(self::getServerAddress(), $domain, ExternalSessionManager::getSession($domain));
            }

            try
            {
                $client = new SocialClient(self::getServerAddress(), $domain);
                $client->verificationAuthenticate();
            }
            catch (Exception $e)
            {
                throw new ResolutionException(sprintf('Failed to connect to remote server %s: %s', $domain, $e->getMessage()), $e->getCode(), $e);
            }

            if(!$client->getSessionState()->isAuthenticated())
            {
                throw new ResolutionException(sprintf('Failed to authenticate with remote server %s', $domain));
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
         * Verifies the signature of a message using the public key of the signing peer both locally and externally.
         * If the peer is registered locally, the signature is verified using the local public key.
         * If the peer is external, the signature is verified by resolving the peer's public key from the external server.
         * The signature is verified against the resolved public key, and the status of the verification is returned.
         *
         * @param PeerAddress|string $signingPeer The peer address or string identifier of the signing peer
         * @param string $signatureUuid The UUID of the signature key to be resolved
         * @param string $signature  The signature to be verified
         * @param string $messageHash The SHA-512 hash of the message that was signed
         * @param int $signatureTime The time at which the message was signed
         * @return SignatureVerificationStatus The status of the signature verification
         */
        public static function verifyTimedSignature(PeerAddress|string $signingPeer, string $signatureUuid, string $signature, string $messageHash, int $signatureTime): SignatureVerificationStatus
        {
            if(!Validator::validateUuid($signatureUuid))
            {
                return SignatureVerificationStatus::INVALID;
            }

            if(!Cryptography::validateSha512($messageHash))
            {
                return SignatureVerificationStatus::INVALID;
            }

            // Resolve the peer signature key
            try
            {
                $signingKey = self::resolvePeerSignature($signingPeer, $signatureUuid);
                if($signingKey === null)
                {
                    return SignatureVerificationStatus::NOT_FOUND;
                }
            }
            catch(StandardRpcException)
            {
                return SignatureVerificationStatus::RESOLUTION_ERROR;
            }

            if(time() > $signingKey->getExpires())
            {
                return SignatureVerificationStatus::EXPIRED;
            }

            // Verify the signature with the resolved key
            try
            {
                if (!Cryptography::verifyTimedMessage($messageHash, $signature, $signingKey->getPublicKey(), $signatureTime, false))
                {
                    return SignatureVerificationStatus::INVALID;
                }
            }
            catch (CryptographyException)
            {
                return SignatureVerificationStatus::ERROR;
            }

            return SignatureVerificationStatus::VERIFIED;
        }

        /**
         * Verifies the signature of a message using the public key of the signing peer both locally and externally.
         * If the peer is registered locally, the signature is verified using the local public key.
         * If the peer is external, the signature is verified by resolving the peer's public key from the external server.
         * The signature is verified against the resolved public key, and the status of the verification is returned.
         *
         * @param PeerAddress|string $signingPeer The peer address or string identifier of the signing peer
         * @param string $signatureUuid The UUID of the signature key to be resolved
         * @param string $signature The signature to be verified
         * @param string $messageHash The SHA-512 hash of the message that was signed
         * @return SignatureVerificationStatus The status of the signature verification
         */
        public static function verifySignature(PeerAddress|string $signingPeer, string $signatureUuid, string $signature, string $messageHash): SignatureVerificationStatus
        {
            if(!Validator::validateUuid($signatureUuid))
            {
                return SignatureVerificationStatus::INVALID;
            }

            if(!Cryptography::validateSha512($messageHash))
            {
                return SignatureVerificationStatus::INVALID;
            }

            try
            {
                $signingKey = self::resolvePeerSignature($signingPeer, $signatureUuid);
                if($signingKey === null)
                {
                    return SignatureVerificationStatus::NOT_FOUND;
                }
            }
            catch(StandardRpcException)
            {
                return SignatureVerificationStatus::RESOLUTION_ERROR;
            }

            // Verify the signature with the resolved key
            try
            {
                if (!Cryptography::verifyMessage($messageHash, $signature, $signingKey->getPublicKey(), false))
                {
                    return SignatureVerificationStatus::INVALID;
                }
            }
            catch (CryptographyException)
            {
                return SignatureVerificationStatus::ERROR;
            }

            return SignatureVerificationStatus::VERIFIED;
        }


        /**
         * Resolves a peer signature key based on the given peer address or string identifier.
         *
         * @param PeerAddress|string $peerAddress The peer address or string identifier to be resolved.
         * @param string $signatureUuid The UUID of the signature key to be resolved.
         * @return Signature|null The resolved signing key for the peer. Null if not found
         * @throws StandardRpcException If there was an error while resolving the peer signature key.
         */
        public static function resolvePeerSignature(PeerAddress|string $peerAddress, string $signatureUuid): ?Signature
        {
            // Convert string peer address to object PeerAddress
            if(is_string($peerAddress))
            {
                $peerAddress = PeerAddress::fromAddress($peerAddress);
            }

            // Prevent resolutions against any host
            if($peerAddress->getUsername() == ReservedUsernames::HOST)
            {
                throw new StandardRpcException('Cannot resolve signature for a host peer', StandardError::FORBIDDEN);
            }

            if(!Validator::validateUuid($signatureUuid))
            {
                throw new InvalidRpcArgumentException('The given signature UUID is not a valid UUID V4');
            }

            // If the peer is registered within this server
            if($peerAddress->getDomain() === Configuration::getInstanceConfiguration()->getDomain())
            {

                try
                {
                    $peer = RegisteredPeerManager::getPeerByAddress($peerAddress);
                    if($peer === null || !$peer?->isEnabled())
                    {
                        // Fail if the peer is not found or enabled
                        return null;
                    }

                    $signingKey = SigningKeysManager::getSigningKey($peer->getUuid(), $signatureUuid);
                    if($signingKey === null)
                    {
                        return null;
                    }
                }
                catch(Exception $e)
                {
                    throw new StandardRpcException('There was an error while trying to resolve the signature key for the peer locally', StandardError::INTERNAL_SERVER_ERROR, $e);
                }

                return $signingKey->toStandard();
            }

            // The requested peer is coming from an external server
            try
            {
                $client = self::getExternalSession($peerAddress->getDomain());
            }
            catch(Exception $e)
            {
                throw new StandardRpcException(sprintf('There was an error while trying to communicate with %s', $peerAddress->getDomain()), StandardError::RESOLUTION_FAILED, $e);
            }

            try
            {
                return $client->resolveSignature($peerAddress, $signatureUuid);
            }
            catch(RpcException $e)
            {
                // Reflect the server error to the client
                throw new StandardRpcException($e->getMessage(), StandardError::tryFrom((int)$e->getCode()) ?? StandardError::UNKNOWN, $e);
            }
        }

        /**
         * Resolves an external peer based on the given peer address or string identifier.
         *
         * @param PeerAddress|string $peerAddress The external peer address or string identifier to be resolved.
         * @param PeerAddress|string|null $identifiedAs Optional. The peer address or string identifier by which the caller is identified
         * @return Peer The resolved external peer after synchronization.
         * @throws StandardRpcException Thrown if there was an error with the resolution process
         */
        public static function resolvePeer(PeerAddress|string $peerAddress, null|PeerAddress|string $identifiedAs=null): Peer
        {
            if(strtolower($peerAddress->getDomain()) !== strtolower(Configuration::getInstanceConfiguration()->getDomain()))
            {
                return self::resolveExternalPeer($peerAddress, $identifiedAs);
            }

            if(strtolower($peerAddress->getUsername()) === strtolower(ReservedUsernames::HOST->value))
            {
                return new Peer([
                    'address' => sprintf('%s@%s', ReservedUsernames::HOST->value, Configuration::getInstanceConfiguration()->getDomain()),
                    'information_fields' => [
                        new InformationField([
                            'name' => InformationFieldName::DISPLAY_NAME,
                            'value' => Configuration::getInstanceConfiguration()->getName()
                        ])
                    ],
                    'flags' => [],
                    // TODO: Should use existed-since field
                    'registered' => 0
                ]);
            }

            return self::resolveLocalPeer($peerAddress, $identifiedAs);
        }

        /**
         * Resolves a peer based on the given peer address or string identifier.
         *
         * @param PeerAddress|string $peerAddress The peer address or string identifier to be resolved.
         * @param PeerAddress|string|null $identifiedAs Optional. The peer address or string identifier by which the caller is identified
         * @return Peer The resolved peer after synchronization.
         * @throws StandardRpcException Thrown if there was an error with the resolution process
         */
        private static function resolveExternalPeer(PeerAddress|string $peerAddress, null|PeerAddress|string $identifiedAs=null): Peer
        {
            if(is_string($peerAddress))
            {
                $peerAddress = PeerAddress::fromAddress($peerAddress);
            }

            if(is_string($identifiedAs))
            {
                $identifiedAs = PeerAddress::fromAddress($identifiedAs);
            }

            Logger::getLogger()->debug(sprintf('Resolving external peer by %s while identified as %s', $peerAddress, $identifiedAs ?? 'nobody'));

            // Always resolve remotely if an identifier has been provided (this is a personal resolution)
            // Otherwise, in all other cases we try to use local peers first and fall back on remote if necessary
            // and use local storage to improve performance when possible
            if($identifiedAs !== null)
            {
                try
                {
                    return self::getExternalSession($peerAddress->getDomain())->resolvePeer($peerAddress, $identifiedAs);
                }
                catch(RpcException $e)
                {
                    throw StandardRpcException::fromRpcException($e);
                }
                catch(Exception $e)
                {
                    throw new StandardRpcException('Failed to resolve the peer: ' . $e->getMessage(), StandardError::RESOLUTION_FAILED, $e);
                }
            }

            // Resolve the peer from the local database if it exists
            try
            {
                $existingPeer = RegisteredPeerManager::getPeerByAddress($peerAddress);
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to resolve the peer due to an internal server error', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            if($existingPeer === null)
            {
                // if the peer doesn't exist, resolve it externally and synchronize it
                try
                {
                    Logger::getLogger()->debug(sprintf('Local peer resolution does not exist, resolving %s externally as %s', $peerAddress->getAddress(), $identifiedAs ?? 'nobody'));
                    $peer = self::getExternalSession($peerAddress->getDomain())->resolvePeer($peerAddress, $identifiedAs);
                }
                catch(RpcException $e)
                {
                    throw StandardRpcException::fromRpcException($e);
                }
                catch(Exception $e)
                {
                    throw new StandardRpcException('Failed to resolve the peer: ' . $e->getMessage(), StandardError::RESOLUTION_FAILED, $e);
                }

                // Do not synchronize if this is a personal request, there may be information fields that
                // the peer does not want to share with the server
                if($identifiedAs !== null)
                {
                    Logger::getLogger()->debug(sprintf('Resolution is not personal, synchronizing peer %s', $peer->getPeerAddress()->getAddress()));
                    try
                    {
                        RegisteredPeerManager::synchronizeExternalPeer($peer);
                    }
                    catch(DatabaseOperationException $e)
                    {
                        throw new StandardRpcException('Failed to synchronize the external peer due to an internal server error', StandardError::INTERNAL_SERVER_ERROR, $e);
                    }
                }
                else
                {
                    Logger::getLogger()->debug(sprintf('Resolution is personal, skipping synchronization for %s', $peer->getPeerAddress()->getAddress()));
                }

                Logger::getLogger()->debug(sprintf('Resolved %s from external server', $peer->getPeerAddress()->getAddress()));
                return $peer;
            }

            // if we're not identifying as a personal peer and If the peer exists, but it's outdated, synchronize it
            if($identifiedAs === null && $existingPeer->getUpdated()->getTimestamp() < time() - Configuration::getPoliciesConfiguration()->getPeerSyncInterval())
            {
                $expired = $existingPeer->getUpdated()->getTimestamp() < time();
                Logger::getLogger()->debug(sprintf('Local peer %s is outdated by %d seconds, synchronizing from external server', $peerAddress, $expired));

                try
                {
                    $peer = self::getExternalSession($peerAddress->getDomain())->resolvePeer($peerAddress, $identifiedAs);
                }
                catch(RpcException $e)
                {
                    throw StandardRpcException::fromRpcException($e);
                }
                catch(Exception $e)
                {
                    throw new StandardRpcException('Failed to resolve the peer: ' . $e->getMessage(), StandardError::RESOLUTION_FAILED, $e);
                }

                try
                {
                    RegisteredPeerManager::synchronizeExternalPeer($peer);
                }
                catch(DatabaseOperationException $e)
                {
                    throw new StandardRpcException('Failed to synchronize the external peer due to an internal server error', StandardError::INTERNAL_SERVER_ERROR, $e);
                }

                return $peer;
            }

            try
            {
                // If the peer exists and is up to date, return it from our local database instead. (Quicker)
                Logger::getLogger()->debug(sprintf('Local peer resolution occurred, resolving locally stored information fields for %s', $existingPeer->getAddress()));
                $informationFields = PeerInformationManager::getFields($existingPeer);
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to obtain local information fields about an external peer locally due to an internal server error', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return new Peer([
                'address' => $existingPeer->getAddress(),
                'information_fields' => $informationFields,
                'flags' => $existingPeer->getFlags(),
                'registered' => $existingPeer->getCreated()->getTimestamp()
            ]);
        }

        /**
         * Resolves a peer locally based on the given peer address or string identifier.
         *
         * @param PeerAddress|string $peerAddress The peer address or string identifier to be resolved.
         * @param PeerAddress|string|null $identifiedAs Optional. The peer address or string identifier by which the caller is identified
         * @return Peer The resolved peer after synchronization.
         * @throws StandardRpcException Thrown if there was an error with the resolution process
         */
        private static function resolveLocalPeer(PeerAddress|string $peerAddress, null|PeerAddress|string $identifiedAs=null): Peer
        {
            if(is_string($peerAddress))
            {
                $peerAddress = PeerAddress::fromAddress($peerAddress);
            }

            if(is_string($identifiedAs))
            {
                $identifiedAs = PeerAddress::fromAddress($identifiedAs);
            }

            Logger::getLogger()->debug(sprintf('Resolving local peer %s as %s', $peerAddress, $identifiedAs ?? 'nobody'));

            // Resolve the peer
            try
            {
                $peer = RegisteredPeerManager::getPeerByAddress($peerAddress);
                if($peer === null)
                {
                    throw new StandardRpcException('The requested peer was not found', StandardError::PEER_NOT_FOUND);
                }
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to resolve the peer: ' . $e->getMessage(), StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            try
            {
                // Get the initial peer information fields, public always
                $peerInformationFields = PeerInformationManager::getFilteredFields($peer, [PrivacyState::PUBLIC]);
                Logger::getLogger()->debug(sprintf('Retrieved %d public information fields from %s', count($peerInformationFields), $peer->getAddress()));
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to resolve local peer information', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            // If there's an identifier, we can resolve more information fields if the target peer has added the caller
            // as a contact or if the caller is a trusted contact
            if($identifiedAs !== null)
            {
                try
                {
                    $peerContact = ContactManager::getContact($peer->getUuid(), $identifiedAs);
                }
                catch (DatabaseOperationException $e)
                {
                    throw new StandardRpcException('Failed to resolve peer because there was an error while trying to retrieve contact information for the peer', StandardError::INTERNAL_SERVER_ERROR, $e);
                }

                // If it is a contact, what sort of contact? retrieve depending on the contact type
                if($peerContact !== null)
                {
                    Logger::getLogger()->debug(sprintf('Peer resolution notice, %s has a contact for %s as %s, resolving additional information fields', $peer->getAddress(), $identifiedAs, $peerContact->getRelationship()->value));

                    try
                    {
                        if($peerContact->getRelationship() === ContactRelationshipType::MUTUAL)
                        {
                            Logger::getLogger()->debug(sprintf('Resolving mutual information fields for %s', $peer->getAddress()));

                            // Retrieve the mutual information fields
                            $peerInformationFields = array_merge($peerInformationFields, PeerInformationManager::getFilteredFields($peer, [PrivacyState::CONTACTS]));
                        }
                        elseif($peerContact->getRelationship() === ContactRelationshipType::TRUSTED)
                        {
                            Logger::getLogger()->debug(sprintf('Resolving trusted information fields for %s', $peer->getAddress()));

                            // Retrieve the mutual and trusted information fields
                            $peerInformationFields = array_merge($peerInformationFields, PeerInformationManager::getFilteredFields($peer, [PrivacyState::CONTACTS, PrivacyState::TRUSTED]));
                        }
                        else
                        {
                            Logger::getLogger()->debug(sprintf('No additional information fields to resolve for %s', $peer->getAddress()));
                        }
                    }
                    catch (DatabaseOperationException $e)
                    {
                        throw new StandardRpcException('Failed to resolve local peer information', StandardError::INTERNAL_SERVER_ERROR, $e);
                    }
                }
            }

            return new Peer([
                'address' => $peer->getAddress(),
                'information_fields' => $peerInformationFields,
                'flags' => PeerFlags::toString($peer->getFlags()),
                'registered' => $peer->getCreated()->getTimestamp()
            ]);
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