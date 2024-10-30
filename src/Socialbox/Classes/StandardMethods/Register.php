<?php

namespace Socialbox\Classes\StandardMethods;

use Socialbox\Abstracts\Method;
use Socialbox\Classes\Configuration;
use Socialbox\Classes\Validator;
use Socialbox\Enums\StandardError;
use Socialbox\Exceptions\DatabaseOperationException;
use Socialbox\Exceptions\StandardException;
use Socialbox\Interfaces\SerializableInterface;
use Socialbox\Managers\RegisteredPeerManager;
use Socialbox\Managers\SessionManager;
use Socialbox\Objects\ClientRequest;
use Socialbox\Objects\RpcRequest;

class Register extends Method
{
    /**
     * @inheritDoc
     */
    public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
    {
        if(!Configuration::getRegistrationConfiguration()->isRegistrationEnabled())
        {
            return $rpcRequest->produceError(StandardError::REGISTRATION_DISABLED, StandardError::REGISTRATION_DISABLED->getMessage());
        }

        // Check if the username parameter exists
        if(!$rpcRequest->containsParameter('username'))
        {
            return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'Missing parameter \'username\'');
        }

        // Check if the username is valid
        if(!Validator::validateUsername($rpcRequest->getParameter('username')))
        {
            return $rpcRequest->produceError(StandardError::INVALID_USERNAME, StandardError::INVALID_USERNAME->getMessage());
        }

        // Check if the username exists already
        try
        {
            if (RegisteredPeerManager::usernameExists($rpcRequest->getParameter('username')))
            {
                return $rpcRequest->produceError(StandardError::USERNAME_ALREADY_EXISTS, StandardError::USERNAME_ALREADY_EXISTS->getMessage());
            }
        }
        catch (DatabaseOperationException $e)
        {
            throw new StandardException("There was an unexpected error while trying to check the username existence", StandardError::INTERNAL_SERVER_ERROR, $e);
        }

        // Check if the request has a Session UUID
        if($request->getSessionUuid() === null)
        {
            return $rpcRequest->produceError(StandardError::SESSION_REQUIRED);
        }

        try
        {
            // Get the session and check if it's already authenticated
            $session = SessionManager::getSession($request->getSessionUuid());
            if($session->getPeerUuid() !== null)
            {
                return $rpcRequest->produceError(StandardError::ALREADY_AUTHENTICATED);
            }

            // Create the peer & set the current's session authenticated peer as the newly created peer
            SessionManager::updatePeer($session->getUuid(), RegisteredPeerManager::createPeer($rpcRequest->getParameter('username')));
        }
        catch(DatabaseOperationException $e)
        {
            throw new StandardException("There was an unexpected error while trying to register", StandardError::INTERNAL_SERVER_ERROR, $e);
        }

        // Return true to indicate the operation was a success
        return $rpcRequest->produceResponse(true);
    }
}