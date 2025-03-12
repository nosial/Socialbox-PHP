<?php

    namespace Socialbox\Classes\StandardMethods\Settings;

    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\PasswordManager;
    use Socialbox\Managers\SessionManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class SettingsSetPassword extends Method
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
            try
            {
                if (PasswordManager::usesPassword($request->getPeer()->getUuid()))
                {
                    return $rpcRequest->produceError(StandardError::METHOD_NOT_ALLOWED, "Cannot set password when one is already set, use 'settingsUpdatePassword' instead");
                }
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to check password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            try
            {
                // Set the password
                PasswordManager::setPassword($request->getPeer(), (string)$rpcRequest->getParameter('password'));

                // Remove the SET_PASSWORD flag & update the session flow if necessary
                SessionManager::updateFlow($request->getSession(), [SessionFlags::SET_PASSWORD]);
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