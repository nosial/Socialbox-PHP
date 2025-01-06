<?php

    namespace Socialbox\Classes\StandardMethods;

    use Exception;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\PasswordManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class SettingsDeletePassword extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(Configuration::getRegistrationConfiguration()->isPasswordRequired())
            {
                return $rpcRequest->produceError(StandardError::FORBIDDEN, 'A password is required for this server');
            }

            if(!$rpcRequest->containsParameter('password'))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Missing 'password' parameter");
            }

            if(!Cryptography::validateSha512($rpcRequest->getParameter('password')))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Invalid 'password' parameter, must be a valid SHA-512 hash");
            }

            $peer = $request->getPeer();

            try
            {
                if (!PasswordManager::usesPassword($peer->getUuid()))
                {
                    return $rpcRequest->produceError(StandardError::METHOD_NOT_ALLOWED, "Cannot update password when one isn't already set, use 'settingsSetPassword' instead");
                }

                if (!PasswordManager::verifyPassword($peer->getUuid(), $rpcRequest->getParameter('password')))
                {
                    return $rpcRequest->produceError(StandardError::FORBIDDEN, "Failed to update password due to incorrect existing password");
                }

                // Set the password
                PasswordManager::updatePassword($peer->getUuid(), $rpcRequest->getParameter('password'));
            }
            catch(CryptographyException)
            {
                return $rpcRequest->produceError(StandardError::CRYPTOGRAPHIC_ERROR, 'Failed to update password due to a cryptographic error');
            }
            catch (Exception $e)
            {
                throw new StandardException('Failed to check password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse(true);
        }
    }