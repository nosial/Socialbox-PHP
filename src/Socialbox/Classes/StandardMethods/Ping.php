<?php

    namespace Socialbox\Classes\StandardMethods;

    use Socialbox\Abstracts\Method;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\ClientRequestOld;
    use Socialbox\Objects\RpcRequest;

    class Ping extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            return $rpcRequest->produceResponse(true);
        }
    }