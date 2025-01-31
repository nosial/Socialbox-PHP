<?php

    namespace Socialbox\Classes\StandardMethods\Settings;

    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\InvalidRpcArgumentException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
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
                throw new MissingRpcArgumentException('password');
            }

            if(!Cryptography::validatePasswordHash($rpcRequest->getParameter('password')))
            {
                throw new InvalidRpcArgumentException('password', 'Must be a valid argon2id hash');
            }

            if(!$rpcRequest->containsParameter('existing_password'))
            {
                throw new MissingRpcArgumentException('existing_password');
            }

            if(!Cryptography::validateSha512($rpcRequest->getParameter('existing_password')))
            {
                throw new InvalidRpcArgumentException('existing_password', 'Must be a valid SHA-512 hash');
            }

            try
            {
                if (!PasswordManager::usesPassword($request->getPeer()->getUuid()))
                {
                    return $rpcRequest->produceResponse(false);
                }
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to check password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            try
            {
                if (!PasswordManager::verifyPassword($request->getPeer()->getUuid(), $rpcRequest->getParameter('existing_password')))
                {
                    return $rpcRequest->produceResponse(false);
                }
            }
            catch(CryptographyException $e)
            {
                throw new StandardRpcException($e->getMessage(), StandardError::CRYPTOGRAPHIC_ERROR, $e);
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to verify existing password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            try
            {
                // Set the password
                PasswordManager::updatePassword($request->getPeer(), $rpcRequest->getParameter('password'));
            }
            catch(CryptographyException $e)
            {
                throw new StandardRpcException($e->getMessage(), StandardError::CRYPTOGRAPHIC_ERROR, $e);
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to set password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse(true);
        }
    }