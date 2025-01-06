<?php

    namespace Socialbox\Classes\StandardMethods;

    use Exception;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Validator;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\RegisteredPeerManager;
    use Socialbox\Managers\SessionManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class SettingsSetPhoneNumber extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(!$rpcRequest->containsParameter('phone_number'))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Missing 'phone_number' parameter");
            }

            if(!Validator::validatePhoneNumber($rpcRequest->getParameter('phone_number')))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Invalid 'phone_number' parameter, must be a valid phone number");
            }

            try
            {
                // Set the phone number
                RegisteredPeerManager::updatePhoneNumber($request->getPeer(), $rpcRequest->getParameter('phone_number'));

                // Check & update the session flow
                SessionManager::updateFlow($request->getSession(), [SessionFlags::SET_PHONE]);
            }
            catch(Exception $e)
            {
                throw new StandardException('Failed to set phone number due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse(true);
        }
    }