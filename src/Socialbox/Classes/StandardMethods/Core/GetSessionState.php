<?php

    namespace Socialbox\Classes\StandardMethods\Core;

    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
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
            try
            {
                return $rpcRequest->produceResponse($request->getSession()->toStandardSessionState());
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to retrieve session state due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }
        }
    }