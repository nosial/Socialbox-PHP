<?php

    namespace Socialbox\Classes\StandardMethods;

    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\StandardError;
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
            if($request->getSessionUuid() === null)
            {
                return $rpcRequest->produceError(StandardError::SESSION_REQUIRED);
            }

            return $rpcRequest->produceResponse($request->getSession()->toStandardSessionState());
        }
    }