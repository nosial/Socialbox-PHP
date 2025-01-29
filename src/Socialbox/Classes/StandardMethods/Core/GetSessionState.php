<?php

    namespace Socialbox\Classes\StandardMethods\Core;

    use Socialbox\Abstracts\Method;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class GetSessionState extends Method
    {

        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            return $rpcRequest->produceResponse($request->getSession()->toStandardSessionState());
        }
    }