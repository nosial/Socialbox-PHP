<?php

    namespace Socialbox\Classes\StandardMethods\Settings;

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
         * Executes the password deletion process, validating the required parameters
         * and deleting the password if the existing password is correct.
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            // Prevent deletion of password if it is required
            if(Configuration::getRegistrationConfiguration()->isPasswordRequired())
            {
                return $rpcRequest->produceError(StandardError::FORBIDDEN, 'A password is required for this server');
            }

            // Check if the password parameter is present
            if(!$rpcRequest->containsParameter('password'))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Missing 'password' parameter");
            }

            // Validate the password parameter
            if(!Cryptography::validateSha512($rpcRequest->getParameter('password')))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Invalid 'password' parameter, must be a valid SHA-512 hash");
            }

            // Get the peer
            $peer = $request->getPeer();

            try
            {
                // Check if the password is set
                if (!PasswordManager::usesPassword($peer->getUuid()))
                {
                    return $rpcRequest->produceError(StandardError::METHOD_NOT_ALLOWED, "Cannot delete password when one isn't already set");
                }

                // Verify the existing password before deleting it
                if (!PasswordManager::verifyPassword($peer->getUuid(), $rpcRequest->getParameter('password')))
                {
                    return $rpcRequest->produceError(StandardError::FORBIDDEN, "Failed to delete password due to incorrect existing password");
                }

                // Delete the password
                PasswordManager::deletePassword($peer->getUuid());
            }
            catch(CryptographyException)
            {
                return $rpcRequest->produceError(StandardError::CRYPTOGRAPHIC_ERROR, 'Failed to delete password due to a cryptographic error');
            }
            catch (Exception $e)
            {
                throw new StandardException('Failed to check password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            // Success
            return $rpcRequest->produceResponse(true);
        }
    }