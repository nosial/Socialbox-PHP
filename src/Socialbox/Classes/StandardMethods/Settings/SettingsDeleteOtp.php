<?php

    namespace Socialbox\Classes\StandardMethods\Settings;

    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\InvalidRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\OneTimePasswordManager;
    use Socialbox\Managers\PasswordManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class SettingsDeleteOtp extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(Configuration::getRegistrationConfiguration()->isOtpRequired())
            {
                return $rpcRequest->produceError(StandardError::FORBIDDEN, 'One Time Password is required for this server');
            }

            try
            {
                $peer = $request->getPeer();
                if (!OneTimePasswordManager::usesOtp($peer->getUuid()))
                {
                    return $rpcRequest->produceResponse(false);
                }
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to check One Time Password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            try
            {
                $usesPassword = PasswordManager::usesPassword($peer);
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to check password usage due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            // Password verification is required to set an OTP if a password is set
            if($usesPassword)
            {
                if(!$rpcRequest->containsParameter('password'))
                {
                    throw new InvalidRpcArgumentException('password', 'When a password is set, the current password must be provided to delete an OTP');
                }

                try
                {
                    if(!PasswordManager::verifyPassword($peer, (string)$rpcRequest->getParameter('password')))
                    {
                        return $rpcRequest->produceError(StandardError::FORBIDDEN, 'The provided password is incorrect');
                    }
                }
                catch(CryptographyException $e)
                {
                    throw new StandardRpcException($e->getMessage(), StandardError::CRYPTOGRAPHIC_ERROR, $e);
                }
                catch(DatabaseOperationException $e)
                {
                    throw new StandardRpcException('Failed to verify password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
                }
            }

            try
            {
                // Delete the OTP
                OneTimePasswordManager::deleteOtp($peer);
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to set password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse(true);
        }
    }