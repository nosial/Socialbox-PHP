<?php

namespace Socialbox\Classes;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use Socialbox\Enums\StandardHeaders;
use Socialbox\Exceptions\DatabaseOperationException;
use Socialbox\Exceptions\RequestException;
use Socialbox\Exceptions\RpcException;
use Socialbox\Exceptions\StandardException;
use Socialbox\Managers\SessionManager;
use Socialbox\Objects\ClientRequestOld;
use Socialbox\Objects\RpcRequest;

class RpcHandler
{
    /**
     * Gets the incoming ClientRequest object, validates if the request is valid & if a session UUID is provided
     * checks if the request signature matches the client's provided public key.
     *
     * @return ClientRequestOld The parsed ClientRequest object
     * @throws RequestException
     * @throws RpcException Thrown if the request is invalid
     */
    public static function getClientRequest(): ClientRequestOld
    {
        try
        {
            $headers = Utilities::getRequestHeaders();
            foreach(StandardHeaders::getRequiredHeaders() as $header)
            {
                if (!isset($headers[$header]))
                {
                    throw new RequestException("Missing required header: $header", 400);
                }

                // Validate the headers
                switch(StandardHeaders::tryFrom($header))
                {
                    case StandardHeaders::CLIENT_VERSION:
                        if($headers[$header] !== '1.0')
                        {
                            throw new RpcException(sprintf("Unsupported Client Version: %s", $headers[$header]));
                        }
                        break;

                    case StandardHeaders::CONTENT_TYPE:
                        if(!str_contains($headers[$header], 'application/json'))
                        {
                            throw new RpcException(sprintf("Invalid Content-Type header: Expected application/json, got %s", $headers[$header]), 400);
                        }
                        break;

                    case StandardHeaders::FROM_PEER:
                        if(!Validator::validatePeerAddress($headers[$header]))
                        {
                            throw new RpcException("Invalid From-Peer header: " . $headers[$header], 400);
                        }
                        break;

                    default:
                        break;
                }
            }
        }
        catch(RuntimeException $e)
        {
            throw new RpcException("Failed to parse request: " . $e->getMessage(), 400, $e);
        }

        $clientRequest = new ClientRequestOld($headers, self::getRpcRequests(), self::getRequestHash());

        // Verify the session & request signature
        if($clientRequest->getSessionUuid() !== null)
        {
            // If no signature is provided, it must be required if the client is providing a Session UUID
            if($clientRequest->getSignature() === null)
            {
                throw new RpcException(sprintf('Unauthorized request, signature required for session based requests'), 401);
            }

            try
            {
                $session = SessionManager::getSession($clientRequest->getSessionUuid());
            }
            catch(StandardException $e)
            {
                throw new RpcException($e->getMessage(), 400);
            }
            catch(DatabaseOperationException $e)
            {
                throw new RpcException('Failed to verify session', 500, $e);
            }

            try
            {
                if(!Cryptography::verifyContent($clientRequest->getHash(), $clientRequest->getSignature(), $session->getPublicKey()))
                {
                    throw new RpcException('Request signature check failed', 400);
                }
            }
            catch(RpcException $e)
            {
                throw $e;
            }
            catch(Exception $e)
            {
                throw new RpcException('Request signature check failed (Cryptography Error): ' . $e->getMessage(), 400, $e);
            }
        }

        return $clientRequest;
    }

    /**
     * Returns the request hash by hashing the request body using SHA256
     *
     * @return string Returns the request hash in SHA256 representation
     */
    private static function getRequestHash(): string
    {
        return hash('sha1', file_get_contents('php://input'));
    }

    /**
     * Handles a POST request, returning an array of RpcRequest objects
     * expects a JSON encoded body with either a single RpcRequest object or an array of RpcRequest objects
     *
     * @return RpcRequest[] The parsed RpcRequest objects
     * @throws RpcException Thrown if the request is invalid
     */
    private static function getRpcRequests(): array
    {
        try
        {
            // Decode the request body
            $body = Utilities::jsonDecode(file_get_contents('php://input'));
        }
        catch(InvalidArgumentException $e)
        {
            throw new RpcException("Invalid JSON in request body: " . $e->getMessage(), 400, $e);
        }

        if(isset($body['method']))
        {
            // If it only contains a method, we assume it's a single request
            return [self::parseRequest($body)];
        }

        // Otherwise, we assume it's an array of requests
        return array_map(fn($request) => self::parseRequest($request), $body);
    }

    /**
     * Parses the raw request data into an RpcRequest object
     *
     * @param array $data The raw request data
     * @return RpcRequest The parsed RpcRequest object
     * @throws RpcException If the request is invalid
     */
    private static function parseRequest(array $data): RpcRequest
    {
        if(!isset($data['method']))
        {
            throw new RpcException("Missing 'method' key in request", 400);
        }

        if(isset($data['id']))
        {
            if(!is_string($data['id']))
            {
                throw new RpcException("Invalid 'id' key in request: Expected string", 400);
            }

            if(strlen($data['id']) === 0)
            {
                throw new RpcException("Invalid 'id' key in request: Expected non-empty string", 400);
            }

            if(strlen($data['id']) > 8)
            {
                throw new RpcException("Invalid 'id' key in request: Expected string of length <= 8", 400);
            }
        }

        if(isset($data['parameters']))
        {
            if(!is_array($data['parameters']))
            {
                throw new RpcException("Invalid 'parameters' key in request: Expected array", 400);
            }
        }

        return new RpcRequest($data['method'], $data['id'] ?? null, $data['parameters'] ?? null);
    }
}