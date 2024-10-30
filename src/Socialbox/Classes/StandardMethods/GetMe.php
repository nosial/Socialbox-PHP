<?php

namespace Socialbox\Classes\StandardMethods;

use Socialbox\Abstracts\Method;
use Socialbox\Classes\Logger;
use Socialbox\Enums\StandardError;
use Socialbox\Exceptions\DatabaseOperationException;
use Socialbox\Exceptions\StandardException;
use Socialbox\Interfaces\SerializableInterface;
use Socialbox\Managers\RegisteredPeerManager;
use Socialbox\Managers\SessionManager;
use Socialbox\Objects\ClientRequest;
use Socialbox\Objects\RpcRequest;

class GetMe extends Method
{

    /**
     * @inheritDoc
     */
    public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
    {
        // Check if the request has a Session UUID
        if($request->getSessionUuid() === null)
        {
            return $rpcRequest->produceError(StandardError::SESSION_REQUIRED);
        }

        try
        {
            // Get the session and check if it's already authenticated
            $session = SessionManager::getSession($request->getSessionUuid());
            if($session->getAuthenticatedPeerUuid() === null)
            {
                return $rpcRequest->produceError(StandardError::AUTHENTICATION_REQUIRED);
            }

            // Get the peer and return it
            return $rpcRequest->produceResponse(RegisteredPeerManager::getPeer($session->getAuthenticatedPeerUuid())->toSelfUser());
        }
        catch(DatabaseOperationException $e)
        {
            throw new StandardException("There was an unexpected error while trying to register", StandardError::INTERNAL_SERVER_ERROR, $e);
        }
    }
}