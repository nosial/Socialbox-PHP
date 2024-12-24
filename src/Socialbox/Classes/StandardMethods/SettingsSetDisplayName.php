<?php

    namespace Socialbox\Classes\StandardMethods;

    use Exception;
    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\RegisteredPeerManager;
    use Socialbox\Managers\SessionManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class SettingsSetDisplayName extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(!$rpcRequest->containsParameter('name'))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Missing 'name' parameter");
            }

            try
            {
                // Set the password
                RegisteredPeerManager::updateDisplayName($request->getPeer(), $rpcRequest->getParameter('name'));

                // Remove the SET_PASSWORD flag
                SessionManager::removeFlags($request->getSessionUuid(), [SessionFlags::SET_DISPLAY_NAME]);

                // Check & update the session flow
                SessionManager::updateFlow($request->getSession());
            }
            catch(Exception $e)
            {
                throw new StandardException('Failed to set password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse(true);
        }
    }