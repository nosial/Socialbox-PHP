<?php

    namespace Socialbox\Classes\StandardMethods\Verification;

    use Exception;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\InvalidRpcArgumentException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\OneTimePasswordManager;
    use Socialbox\Managers\SessionManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class VerificationOtpAuthentication extends Method
    {

        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(!$rpcRequest->containsParameter('code'))
            {
                throw new MissingRpcArgumentException('code');
            }

            if(strlen($rpcRequest->getParameter('code')) !== Configuration::getSecurityConfiguration()->getOtpDigits())
            {
                throw new InvalidRpcArgumentException('code', 'Invalid OTP code length');
            }

            try
            {
                $session = $request->getSession();
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('An error occurred while trying to get the session', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            if(!$session->flagExists(SessionFlags::VER_OTP))
            {
                return $rpcRequest->produceError(StandardError::METHOD_NOT_ALLOWED, 'One Time Password verification is not required at this time');
            }

            try
            {
                $result = OneTimePasswordManager::verifyOtp($request->getPeer(), $rpcRequest->getParameter('code'));

                if($result)
                {
                    // If the OTP is verified, remove the OTP verification flag
                    SessionManager::updateFlow($session, [SessionFlags::VER_OTP]);
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