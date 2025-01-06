<?php

    namespace Socialbox\Classes\StandardMethods;

    use Exception;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\PasswordManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class SettingsUpdatePassword extends Method
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

            if(!Cryptography::validatePasswordHash($rpcRequest->getParameter('password')))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Invalid 'password' parameter, must be a valid argon2id hash");
            }

            if(!$rpcRequest->containsParameter('existing_password'))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Missing 'existing_password' parameter");
            }

            if(!Cryptography::validateSha512($rpcRequest->getParameter('existing_password')))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Invalid 'existing_password' parameter, must be a valid SHA-512 hash");
            }

            try
            {
                if (!PasswordManager::usesPassword($request->getPeer()->getUuid()))
                {
                    return $rpcRequest->produceError(StandardError::METHOD_NOT_ALLOWED, "Cannot update password when one isn't already set, use 'settingsSetPassword' instead");
                }
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardException('Failed to check password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            try
            {
                if (!PasswordManager::verifyPassword($request->getPeer()->getUuid(), $rpcRequest->getParameter('existing_password')))
                {
                    return $rpcRequest->produceError(StandardError::METHOD_NOT_ALLOWED, "Failed to update password due to incorrect existing password");
                }
            }
            catch (Exception $e)
            {
                throw new StandardException('Failed to verify existing password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            try
            {
                // Set the password
                PasswordManager::updatePassword($request->getPeer(), $rpcRequest->getParameter('password'));
            }
            catch(Exception $e)
            {
                throw new StandardException('Failed to set password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse(true);
        }
    }