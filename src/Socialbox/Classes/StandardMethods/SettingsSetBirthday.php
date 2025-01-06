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

    class SettingsSetBirthday extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(!$rpcRequest->containsParameter('email_address'))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Missing 'email_address' parameter");
            }

            if(!Validator::validateEmailAddress($rpcRequest->getParameter('email_address')))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Invalid 'email_address' parameter, must be a valid email address");
            }

            try
            {
                // Set the password
                RegisteredPeerManager::updateEmailAddress($request->getPeer(), $rpcRequest->getParameter('email_address'));

                // Check & update the session flow
                SessionManager::updateFlow($request->getSession(), [SessionFlags::SET_EMAIL]);
            }
            catch(Exception $e)
            {
                throw new StandardException('Failed to set email address due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse(true);
        }
    }