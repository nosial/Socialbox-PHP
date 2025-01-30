<?php

    namespace Socialbox\Classes\StandardMethods\Settings;

    use Exception;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\OneTimePasswordManager;
    use Socialbox\Managers\PasswordManager;
    use Socialbox\Managers\SessionManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class SettingsSetOtp extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            $peer = $request->getPeer();

            try
            {
                if (OneTimePasswordManager::usesOtp($peer->getUuid()))
                {
                    return $rpcRequest->produceError(StandardError::METHOD_NOT_ALLOWED, "Cannot set One Time Password when one is already set, use 'settingsUpdateOtp' instead");
                }
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to check One Time Password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            if($peer->isEnabled())
            {
                try
                {
                    // If the peer is disabled, the password is not used because we assume the peer is registering
                    $usesPassword = PasswordManager::usesPassword($peer);
                }
                catch (DatabaseOperationException $e)
                {
                    throw new StandardRpcException('Failed to check password usage due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
                }
            }
            else
            {
                $usesPassword = false;
            }

            // Password verification is required to set an OTP if a password is set
            if($usesPassword)
            {
                if(!$rpcRequest->containsParameter('password'))
                {
                    return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'When a password is set, the current password must be provided to set an OTP');
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
                    throw new StandardRpcException('Failed to verify password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
                }
            }

            try
            {
                // Create a new OTP and return the OTP URI to the client
                $totpUri = OneTimePasswordManager::createOtp($peer);

                // Remove the SET_PASSWORD flag & update the session flow if necessary
                SessionManager::updateFlow($request->getSession(), [SessionFlags::SET_OTP]);
            }
            catch(Exception $e)
            {
                throw new StandardRpcException('Failed to set password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse($totpUri);
        }
    }