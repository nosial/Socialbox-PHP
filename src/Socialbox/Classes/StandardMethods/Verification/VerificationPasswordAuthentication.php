<?php

    namespace Socialbox\Classes\StandardMethods\Verification;

    use Exception;
    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\PasswordManager;
    use Socialbox\Managers\SessionManager;
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
                throw new MissingRpcArgumentException('password');
            }

            try
            {
                $session = $request->getSession();
                if(!$session->flagExists(SessionFlags::VER_PASSWORD))
                {
                    return $rpcRequest->produceError(StandardError::METHOD_NOT_ALLOWED, 'Password verification is not required at this time');
                }

                $result = PasswordManager::verifyPassword($request->getPeer(), $rpcRequest->getParameter('password'));

                if($result)
                {
                    SessionManager::updateFlow($request->getSession(), [SessionFlags::VER_PASSWORD]);
                }
            }
            catch (CryptographyException)
            {
                return $rpcRequest->produceResponse(false);
            }
            catch (Exception $e)
            {
                throw new StandardRpcException('Failed to verify password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse($result);
        }
    }