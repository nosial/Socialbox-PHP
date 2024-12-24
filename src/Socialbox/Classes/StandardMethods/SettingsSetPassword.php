<?php

    namespace Socialbox\Classes\StandardMethods;

    use Exception;
    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\PasswordManager;
    use Socialbox\Managers\SessionManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class SettingsSetPassword extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(!$rpcRequest->containsParameter('password'))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Missing 'password' parameter");
            }

            if(!preg_match('/^[a-f0-9]{128}$/', $rpcRequest->getParameter('password')))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Invalid 'password' parameter, must be sha512 hexadecimal hash");
            }

            try
            {
                if (PasswordManager::usesPassword($request->getPeer()->getUuid()))
                {
                    return $rpcRequest->produceError(StandardError::METHOD_NOT_ALLOWED, "Cannot set password when one is already set, use 'settingsChangePassword' instead");
                }
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardException('Failed to check password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            try
            {
                // Set the password
                PasswordManager::setPassword($request->getPeer(), $rpcRequest->getParameter('password'));

                // Remove the SET_PASSWORD flag
                SessionManager::removeFlags($request->getSessionUuid(), [SessionFlags::SET_PASSWORD]);

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