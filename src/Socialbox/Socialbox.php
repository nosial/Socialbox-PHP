<?php

    namespace Socialbox;

    use Exception;
    use InvalidArgumentException;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Classes\Logger;
    use Socialbox\Classes\Utilities;
    use Socialbox\Classes\Validator;
    use Socialbox\Enums\SessionState;
    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\StandardHeaders;
    use Socialbox\Enums\StandardMethods;
    use Socialbox\Enums\Types\RequestType;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\RequestException;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Managers\RegisteredPeerManager;
    use Socialbox\Managers\SessionManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\PeerAddress;

    class Socialbox
    {
        /**
         * Handles incoming client requests by validating required headers and processing
         * the request based on its type. The method ensures proper handling of
         * specific request types like RPC, session initiation, and DHE exchange,
         * while returning an appropriate HTTP response for invalid or missing data.
         *
         * @return void
         */
        public static function handleRequest(): void
        {
            $requestHeaders = Utilities::getRequestHeaders();

            if(!isset($requestHeaders[StandardHeaders::REQUEST_TYPE->value]))
            {
                http_response_code(400);
                print('Missing required header: ' . StandardHeaders::REQUEST_TYPE->value);
                return;
            }

            $clientRequest = new ClientRequest($requestHeaders, file_get_contents('php://input') ?? null);

           // Handle the request type, only `init` and `dhe` are not encrypted using the session's encrypted key
            // RPC Requests must be encrypted and signed by the client, vice versa for server responses.
            switch(RequestType::tryFrom($clientRequest->getHeader(StandardHeaders::REQUEST_TYPE)))
            {
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
                    http_response_code(400);
                    print('Invalid Request-Type header');
                    break;
            }
        }

        /**
         * Processes a client request to initiate a session. Validates required headers,
         * ensures the peer is authorized and enabled, and creates a new session UUID
         * if all checks pass. Handles edge cases like missing headers, invalid inputs,
         * or unauthorized peers.
         *
         * @param ClientRequest $clientRequest The request from the client containing
         *                                      the required headers and information.
         * @return void
         */
        private static function handleInitiateSession(ClientRequest $clientRequest): void
        {

            if(!$clientRequest->getClientName())
            {
                http_response_code(400);
                print('Missing required header: ' . StandardHeaders::CLIENT_NAME->value);
                return;
            }

            if(!$clientRequest->getClientVersion())
            {
                http_response_code(400);
                print('Missing required header: ' . StandardHeaders::CLIENT_VERSION->value);
                return;
            }

            if(!$clientRequest->headerExists(StandardHeaders::PUBLIC_KEY))
            {
                http_response_code(400);
                print('Missing required header: ' . StandardHeaders::PUBLIC_KEY->value);
                return;
            }

            if(!$clientRequest->headerExists(StandardHeaders::IDENTIFY_AS))
            {
                http_response_code(400);
                print('Missing required header: ' . StandardHeaders::IDENTIFY_AS->value);
                return;
            }

            if(!Validator::validatePeerAddress($clientRequest->getHeader(StandardHeaders::IDENTIFY_AS)))
            {
                http_response_code(400);
                print('Invalid Identify-As header: ' . $clientRequest->getHeader(StandardHeaders::IDENTIFY_AS));
                return;
            }

            // If the peer is identifying as the same domain
            if($clientRequest->getIdentifyAs()->getDomain() === Configuration::getInstanceConfiguration()->getDomain())
            {
                // Prevent the peer from identifying as the host unless it's coming from an external domain
               if($clientRequest->getIdentifyAs()->getUsername() === 'host')
               {
                     http_response_code(403);
                     print('Unauthorized: The requested peer is not allowed to identify as the host');
                     return;
               }
            }

            try
            {
                $registeredPeer = RegisteredPeerManager::getPeerByAddress($clientRequest->getIdentifyAs());

                // If the peer is registered, check if it is enabled
                if($registeredPeer !== null && !$registeredPeer->isEnabled())
                {
                    // Refuse to create a session if the peer is disabled/banned
                    // This also prevents multiple sessions from being created for the same peer
                    // A cron job should be used to clean up disabled peers
                    http_response_code(403);
                    print('Unauthorized: The requested peer is disabled/banned');
                    return;
                }
                else
                {
                    // Check if registration is enabled
                    if(!Configuration::getRegistrationConfiguration()->isRegistrationEnabled())
                    {
                        http_response_code(403);
                        print('Unauthorized: Registration is disabled');
                        return;
                    }

                    // Register the peer if it is not already registered
                    $peerUuid = RegisteredPeerManager::createPeer(PeerAddress::fromAddress($clientRequest->getHeader(StandardHeaders::IDENTIFY_AS)));
                    // Retrieve the peer object
                    $registeredPeer = RegisteredPeerManager::getPeer($peerUuid);
                }

                // Create the session UUID
                $sessionUuid = SessionManager::createSession($clientRequest->getHeader(StandardHeaders::PUBLIC_KEY), $registeredPeer, $clientRequest->getClientName(), $clientRequest->getClientVersion());
                http_response_code(201); // Created
                print($sessionUuid); // Return the session UUID
            }
            catch(InvalidArgumentException $e)
            {
                http_response_code(412); // Precondition failed
                print($e->getMessage()); // Why the request failed
            }
            catch(Exception $e)
            {
                Logger::getLogger()->error('An internal error occurred while initiating the session', $e);
                http_response_code(500); // Internal server error
                if(Configuration::getSecurityConfiguration()->isDisplayInternalExceptions())
                {
                    print(Utilities::throwableToString($e));
                }
                else
                {
                    print('An internal error occurred');
                }
            }
        }

        /**
         * Handles the Diffie-Hellman key exchange by decrypting the encrypted key passed on from the client using
         * the server's private key and setting the encryption key to the session.
         *
         *  412: Headers malformed
         *  400: Bad request
         *  500: Internal server error
         *  204: Success, no content.
         *
         * @param ClientRequest $clientRequest
         * @return void
         */
        private static function handleDheExchange(ClientRequest $clientRequest): void
        {
            // Check if the session UUID is set in the headers
            if(!$clientRequest->headerExists(StandardHeaders::SESSION_UUID))
            {
                Logger::getLogger()->verbose('Missing required header: ' . StandardHeaders::SESSION_UUID->value);

                http_response_code(412);
                print('Missing required header: ' . StandardHeaders::SESSION_UUID->value);
                return;
            }

            // Check if the request body is empty
            if(empty($clientRequest->getRequestBody()))
            {
                Logger::getLogger()->verbose('Bad request: The key exchange request body is empty');

                http_response_code(400);
                print('Bad request: The key exchange request body is empty');
                return;
            }

            // Check if the session is awaiting a DHE exchange
            if($clientRequest->getSession()->getState() !== SessionState::AWAITING_DHE)
            {
                Logger::getLogger()->verbose('Bad request: The session is not awaiting a DHE exchange');

                http_response_code(400);
                print('Bad request: The session is not awaiting a DHE exchange');
                return;
            }

            try
            {
                // Attempt to decrypt the encrypted key passed on from the client
                $encryptionKey = Cryptography::decryptContent($clientRequest->getRequestBody(), Configuration::getInstanceConfiguration()->getPrivateKey());
            }
            catch (Exceptions\CryptographyException $e)
            {
                Logger::getLogger()->error(sprintf('Bad Request: Failed to decrypt the key for session %s', $clientRequest->getSessionUuid()), $e);

                http_response_code(400);
                print('Bad Request: Cryptography error, make sure you have encrypted the key using the server\'s public key; ' . $e->getMessage());
                return;
            }

            try
            {
                // Finally set the encryption key to the session
                SessionManager::setEncryptionKey($clientRequest->getSessionUuid(), $encryptionKey);
            }
            catch (DatabaseOperationException $e)
            {
                Logger::getLogger()->error('Failed to set the encryption key for the session', $e);
                http_response_code(500);

                if(Configuration::getSecurityConfiguration()->isDisplayInternalExceptions())
                {
                    print(Utilities::throwableToString($e));
                }
                else
                {
                    print('Internal Server Error: Failed to set the encryption key for the session');
                }

                return;
            }

            Logger::getLogger()->info(sprintf('DHE exchange completed for session %s', $clientRequest->getSessionUuid()));
            http_response_code(204); // Success, no content
        }

        /**
         * Handles incoming RPC requests from a client, processes each request,
         * and returns the appropriate response(s) or error(s).
         *
         * @param ClientRequest $clientRequest The client's request containing one or multiple RPC calls.
         * @return void
         */
        private static function handleRpc(ClientRequest $clientRequest): void
        {
            if(!$clientRequest->headerExists(StandardHeaders::SESSION_UUID))
            {
                Logger::getLogger()->verbose('Missing required header: ' . StandardHeaders::SESSION_UUID->value);

                http_response_code(412);
                print('Missing required header: ' . StandardHeaders::SESSION_UUID->value);
                return;
            }

            try
            {
                $clientRequests = $clientRequest->getRpcRequests();
            }
            catch (RequestException $e)
            {
                http_response_code($e->getCode());
                print($e->getMessage());
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

            try
            {
                $encryptedResponse = Cryptography::encryptTransport($response, $clientRequest->getSession()->getEncryptionKey());
                $signature = Cryptography::signContent($response, Configuration::getInstanceConfiguration()->getPrivateKey(), true);
            }
            catch (Exceptions\CryptographyException $e)
            {
                Logger::getLogger()->error('Failed to encrypt the response', $e);
                http_response_code(500);
                print('Internal Server Error: Failed to encrypt the response');
                return;
            }

            http_response_code(200);
            header('Content-Type: application/octet-stream');
            header(StandardHeaders::SIGNATURE->value . ': ' . $signature);
            print($encryptedResponse);
        }
    }