<?php

    namespace Socialbox\Classes\StandardMethods;

    use Exception;
    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\StandardException;
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
                    return $rpcRequest->produceError(StandardError::FORBIDDEN, 'Only external peers can authenticate');
                }

                if($request->getSession()->isAuthenticated())
                {
                    return $rpcRequest->produceError(StandardError::FORBIDDEN, 'Peer is already authenticated');
                }

                SessionManager::removeFlags($request->getPeer()->getUuid(), [SessionFlags::AUTHENTICATION_REQUIRED]);
                SessionManager::setAuthenticated($request->getPeer()->getUuid(), true);
            }
            catch(Exception $e)
            {
                throw new StandardException('An error occurred while authenticating the peer', StandardError::INTERNAL_SERVER_ERROR, $e);
            }


            return $rpcRequest->produceResponse(true);
        }
    }