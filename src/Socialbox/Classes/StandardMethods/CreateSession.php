<?php

namespace Socialbox\Classes\StandardMethods;

use InvalidArgumentException;
use Socialbox\Abstracts\Method;
use Socialbox\Enums\StandardError;
use Socialbox\Exceptions\DatabaseOperationException;
use Socialbox\Interfaces\SerializableInterface;
use Socialbox\Managers\SessionManager;
use Socialbox\Objects\ClientRequest;
use Socialbox\Objects\RpcRequest;

class CreateSession extends Method
{
    /**
     * Executes the session creation process based on the provided public key.
     *
     * @param ClientRequest $request The client request object.
     * @param RpcRequest $rpcRequest The RPC request containing parameters for execution.
     * @return SerializableInterface|null Returns a response with the session UUID or an error.
     */
    public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
    {
        if(!$rpcRequest->containsParameter('public_key'))
        {
            return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'Missing parameter \'public_key\'');
        }

        try
        {
            $uuid = SessionManager::createSession($rpcRequest->getParameter('public_key'));
        }
        catch(DatabaseOperationException $e)
        {
            return $rpcRequest->produceError(StandardError::INTERNAL_SERVER_ERROR, 'There was an error while trying to create a new session: ' . $e->getMessage());
        }
        catch(InvalidArgumentException $e)
        {
            return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, $e->getMessage());
        }

        return $rpcRequest->produceResponse($uuid);
    }
}