<?php

    namespace Socialbox\Classes\StandardMethods;

    use Exception;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Classes\Logger;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\PasswordManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class VerificationPasswordAuthentication extends Method
    {

        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(!$rpcRequest->containsParameter('password'))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Missing 'password' parameter");
            }

            if(!Cryptography::validateSha512($rpcRequest->getParameter('password')))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Invalid 'password' parameter, must be a valid SHA-512 hash");
            }

            try
            {
                return $rpcRequest->produceResponse(PasswordManager::verifyPassword($request->getPeer()->getUuid(), $rpcRequest->getParameter('password')));
            }
            catch (CryptographyException)
            {
                return $rpcRequest->produceResponse(false);
            }
            catch (Exception $e)
            {
                Logger::getLogger()->error('Failed to verify password due to an internal exception', $e);
                throw new StandardException('Failed to verify password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }
        }
    }