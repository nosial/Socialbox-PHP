<?php

namespace Socialbox\Classes\StandardMethods;

use Socialbox\Abstracts\Method;
use Socialbox\Enums\FirstLevelAuthentication;
use Socialbox\Enums\StandardError;
use Socialbox\Interfaces\SerializableInterface;
use Socialbox\Objects\ClientRequest;
use Socialbox\Objects\RpcRequest;
use Socialbox\Objects\RpcResponse;

class Authenticate extends Method
{
    /**
     * @inheritDoc
     */
    public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
    {
        if(!isset($rpcRequest->getParameters()['type']))
        {
            return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'Missing required parameter \'type\'');
        }

        if(strlen($rpcRequest->getParameters()['type']) == 0)
        {
            return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'Parameter \'type\' cannot be empty');
        }

        return match (FirstLevelAuthentication::tryFrom($rpcRequest->getParameters()['type']))
        {
            FirstLevelAuthentication::PASSWORD => self::handlePassword($request),

            default => $rpcRequest->produceError(StandardError::UNSUPPORTED_AUTHENTICATION_TYPE,
                sprintf('Unsupported authentication type: %s', $rpcRequest->getParameters()['type'])
            ),
        };
    }

    /**
     * Handles the password authentication phase for the peer
     *
     * @param ClientRequest $request
     * @return SerializableInterface
     */
    private static function handlePassword(ClientRequest $request): SerializableInterface
    {

    }
}