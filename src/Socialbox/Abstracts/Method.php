<?php

namespace Socialbox\Abstracts;

use Socialbox\Enums\StandardError;
use Socialbox\Exceptions\DatabaseOperationException;
use Socialbox\Exceptions\StandardException;
use Socialbox\Interfaces\SerializableInterface;
use Socialbox\Managers\SessionManager;
use Socialbox\Objects\ClientRequest;
use Socialbox\Objects\RpcRequest;
use Socialbox\Objects\SessionRecord;

abstract class Method
{
    /**
     * Executes the method and returns RpcResponse/RpcError which implements SerializableInterface
     *
     * @param ClientRequest $request The full client request object, used to identify the client & it's requests
     * @param RpcRequest $rpcRequest The selected RPC request for the method to handle
     * @return SerializableInterface|null Returns RpcResponse/RpcError on success, null if the request is a notification
     * @throws StandardException If a standard exception is thrown, it will be handled by the engine.
     */
    public static abstract function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface;

    /**
     * @param ClientRequest $request The client request object
     * @return SessionRecord|null Returns null if the client has not provided a Session UUID
     * @throws StandardException Thrown if standard exceptions are to be thrown regarding this
     */
    protected static function getSession(ClientRequest $request): ?SessionRecord
    {
        if($request->getSessionUuid() === null)
        {
            return null;
        }

        try
        {
            if(!SessionManager::sessionExists($request->getSessionUuid()))
            {
                throw new StandardException(sprintf("The requested session %s was not found", $request->getSessionUuid()), StandardError::SESSION_NOT_FOUND);
            }

            return SessionManager::getSession($request->getSessionUuid());
        }
        catch(DatabaseOperationException $e)
        {
            throw new StandardException("There was an error while retrieving the session from the server", StandardError::INTERNAL_SERVER_ERROR);
        }
    }
}