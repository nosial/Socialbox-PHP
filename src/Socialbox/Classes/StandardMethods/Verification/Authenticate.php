<?php

    namespace Socialbox\Classes\StandardMethods\Verification;

    use Exception;
    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\SessionManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class Authenticate extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            try
            {
                if(!$request->getPeer()->isExternal())
                {
                    return $rpcRequest->produceError(StandardError::FORBIDDEN, 'Only external peers can authenticate using this method');
                }

                SessionManager::updateFlow($request->getSession(), [SessionFlags::AUTHENTICATION_REQUIRED]);
            }
            catch(Exception $e)
            {
                throw new StandardRpcException('An error occurred while authenticating the peer', StandardError::INTERNAL_SERVER_ERROR, $e);
            }


            return $rpcRequest->produceResponse(true);
        }
    }