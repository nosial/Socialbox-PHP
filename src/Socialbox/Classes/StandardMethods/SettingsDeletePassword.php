<?php

    namespace Socialbox\Classes\StandardMethods;

    use Exception;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
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