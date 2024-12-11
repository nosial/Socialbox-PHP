<?php

    namespace Socialbox\Classes\StandardMethods;

    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Validator;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\RegisteredPeerManager;
    use Socialbox\Managers\SessionManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class Identify extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            // Check if the username parameter exists
            if(!$rpcRequest->containsParameter('username'))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'Missing parameter \'username\'');
            }

            // Check if the username is valid
            if(!Validator::validateUsername($rpcRequest->getParameter('username')))
            {
                return $rpcRequest->produceError(StandardError::INVALID_USERNAME, StandardError::INVALID_USERNAME->getMessage());
            }

            // Check if the request has a Session UUID
            if($request->getSessionUuid() === null)
            {
                return $rpcRequest->produceError(StandardError::SESSION_REQUIRED);
            }

            try
            {
                // Get the session and check if it's already authenticated
                $session = SessionManager::getSession($request->getSessionUuid());

                // If the session is already authenticated, return an error
                if($session->getPeerUuid() !== null)
                {
                    return $rpcRequest->produceError(StandardError::ALREADY_AUTHENTICATED);
                }

                // If the username does not exist, return an error
                if(!RegisteredPeerManager::usernameExists($rpcRequest->getParameter('username')))
                {
                    return $rpcRequest->produceError(StandardError::NOT_REGISTERED, StandardError::NOT_REGISTERED->getMessage());
                }

                // Create session to be identified as the provided username
                SessionManager::updatePeer($session->getUuid(), $rpcRequest->getParameter('username'));

                // Set the required session flags
                $initialFlags = [];
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardException("There was an unexpected error while trying to register", StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            // Return true to indicate the operation was a success
            return $rpcRequest->produceResponse(true);
        }
    }