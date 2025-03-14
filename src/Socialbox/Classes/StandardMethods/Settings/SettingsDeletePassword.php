<?php

    namespace Socialbox\Classes\StandardMethods\Settings;

    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
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
                throw new MissingRpcArgumentException('password');
            }

            try
            {
                // Get the peer
                $requestingPeer = $request->getPeer();

                // Check if the password is set
                if (!PasswordManager::usesPassword($requestingPeer->getUuid()))
                {
                    return $rpcRequest->produceError(StandardError::METHOD_NOT_ALLOWED, "Cannot delete password when one isn't already set");
                }

                // Verify the existing password before deleting it
                if (!PasswordManager::verifyPassword($requestingPeer->getUuid(), (string)$rpcRequest->getParameter('password')))
                {
                    return $rpcRequest->produceResponse(false);
                }

                // Delete the password
                PasswordManager::deletePassword($requestingPeer->getUuid());
            }
            catch(CryptographyException)
            {
                return $rpcRequest->produceError(StandardError::CRYPTOGRAPHIC_ERROR, 'Failed to delete password due to a cryptographic error');
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to delete password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            // Success
            return $rpcRequest->produceResponse(true);
        }
    }