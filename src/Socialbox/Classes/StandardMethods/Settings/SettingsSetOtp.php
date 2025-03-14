<?php

    namespace Socialbox\Classes\StandardMethods\Settings;

    use Exception;
    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
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
            try
            {
                $peer = $request->getPeer();
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
                    throw new MissingRpcArgumentException('password');
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
                catch(DatabaseOperationException  $e)
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