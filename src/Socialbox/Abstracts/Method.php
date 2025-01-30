<?php

    namespace Socialbox\Abstracts;

    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    abstract class Method
    {
        /**
         * Executes the method and returns RpcResponse/RpcError which implements SerializableInterface
         *
         * @param ClientRequest $request The full client request object, used to identify the client & it's requests
         * @param RpcRequest $rpcRequest The selected RPC request for the method to handle
         * @return SerializableInterface|null Returns RpcResponse/RpcError on success, null if the request is a notification
         * @throws StandardRpcException If a standard exception is thrown, it will be handled by the engine.
         */
        public static abstract function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface;
    }