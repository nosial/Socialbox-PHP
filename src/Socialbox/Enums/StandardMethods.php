<?php

namespace Socialbox\Enums;

use Socialbox\Classes\StandardMethods\CreateSession;
use Socialbox\Classes\StandardMethods\Ping;
use Socialbox\Exceptions\StandardException;
use Socialbox\Interfaces\SerializableInterface;
use Socialbox\Objects\ClientRequest;
use Socialbox\Objects\RpcRequest;

enum StandardMethods : string
{
    case PING = 'ping';
    case CREATE_SESSION = 'create_session';

    /**
     * @param ClientRequest $request
     * @param RpcRequest $rpcRequest
     * @return SerializableInterface|null
     * @throws StandardException
     */
    public function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
    {
        return match ($this)
        {
            self::PING => Ping::execute($request, $rpcRequest),
            self::CREATE_SESSION => CreateSession::execute($request, $rpcRequest),
        };
    }
}