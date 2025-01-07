<?php

    namespace Socialbox\Classes\StandardMethods;

    use Exception;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\OneTimePasswordManager;
    use Socialbox\Managers\PasswordManager;
    use Socialbox\Managers\SessionManager;
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
                return $rpcRequest->produceError(StandardError::METHOD_NOT_ALLOWED, 'One Time Password is required for this server');
            }

            $peer = $request->getPeer();

            try
            {
                if (!OneTimePasswordManager::usesOtp($peer->getUuid()))
                {
                    return $rpcRequest->produceError(StandardError::METHOD_NOT_ALLOWED, "Cannot delete One Time Password when none is set");
                }
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardException('Failed to check One Time Password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            try
            {
                $usesPassword = PasswordManager::usesPassword($peer);
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardException('Failed to check password usage due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            // Password verification is required to set an OTP if a password is set
            if($usesPassword)
            {
                if(!$rpcRequest->containsParameter('password'))
                {
                    return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'When a password is set, the current password must be provided to delete an OTP');
                }

                if(!Cryptography::validateSha512($rpcRequest->getParameter('password')))
                {
                    return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'The provided password is not a valid SHA-512 hash');
                }

                try
                {
                    if(!PasswordManager::verifyPassword($peer, $rpcRequest->getParameter('password')))
                    {
                        return $rpcRequest->produceError(StandardError::FORBIDDEN, 'The provided password is incorrect');
                    }
                }
                catch(Exception $e)
                {
                    throw new StandardException('Failed to verify password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
                }
            }

            try
            {
                // Delete the OTP
                OneTimePasswordManager::deleteOtp($peer);
            }
            catch(Exception $e)
            {
                throw new StandardException('Failed to set password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse(true);
        }
    }