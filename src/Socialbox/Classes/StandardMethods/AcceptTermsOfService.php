<?php

    namespace Socialbox\Classes\StandardMethods;

    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\SessionManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class AcceptTermsOfService extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            try
            {
                SessionManager::removeFlags($request->getSessionUuid(), [SessionFlags::VER_TERMS_OF_SERVICE]);
            }
            catch (DatabaseOperationException $e)
            {
                return $rpcRequest->produceError(StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            // Check if all registration flags are removed
            if(SessionFlags::isComplete($request->getSession()->getFlags()))
            {
                // Set the session as authenticated
                try
                {
                    SessionManager::setAuthenticated($request->getSessionUuid(), true);
                    SessionManager::removeFlags($request->getSessionUuid(), [SessionFlags::REGISTRATION_REQUIRED, SessionFlags::AUTHENTICATION_REQUIRED]);
                }
                catch (DatabaseOperationException $e)
                {
                    return $rpcRequest->produceError(StandardError::INTERNAL_SERVER_ERROR, $e);
                }
            }

            return $rpcRequest->produceResponse(true);
        }
    }