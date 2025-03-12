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

    class VerificationAuthenticate extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            try
            {
                if(!$request->isExternal())
                {
                    return $rpcRequest->produceError(StandardError::FORBIDDEN, 'Only external peers can authenticate using this method');
                }

                $session = $request->getSession();

                if(!$session->flagExists(SessionFlags::AUTHENTICATION_REQUIRED))
                {
                    return $rpcRequest->produceResponse(false);
                }

                SessionManager::updateFlow($session, [SessionFlags::AUTHENTICATION_REQUIRED]);
            }
            catch(Exception $e)
            {
                throw new StandardRpcException('An error occurred while authenticating the peer', StandardError::INTERNAL_SERVER_ERROR, $e);
            }


            return $rpcRequest->produceResponse(true);
        }
    }