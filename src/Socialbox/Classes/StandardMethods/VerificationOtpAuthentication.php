<?php

    namespace Socialbox\Classes\StandardMethods;

    use Exception;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\StandardException;
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
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Missing 'code' parameter");
            }

            if(strlen($rpcRequest->getParameter('code')) !== Configuration::getSecurityConfiguration()->getOtpDigits())
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Invalid 'code' parameter length");
            }

            $session = $request->getSession();
            if(!$session->flagExists(SessionFlags::VER_OTP))
            {
                return $rpcRequest->produceError(StandardError::FORBIDDEN, 'One Time Password verification is not required at this time');
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
                throw new StandardException('Failed to verify password due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse($result);
        }
    }