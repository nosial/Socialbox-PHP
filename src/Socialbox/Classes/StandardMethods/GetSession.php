<?php

    namespace Socialbox\Classes\StandardMethods;

    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\RegisteredPeerManager;
    use Socialbox\Managers\SessionManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class GetSession extends Method
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

            try
            {
                // Get the session
                $session = SessionManager::getSession($request->getSessionUuid());
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardException("There was an unexpected error while trying to  retrieve the session", StandardError::INTERNAL_SERVER_ERROR, $e);
            }


        }
    }