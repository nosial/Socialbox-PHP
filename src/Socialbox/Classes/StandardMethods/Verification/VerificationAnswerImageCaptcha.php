<?php

    namespace Socialbox\Classes\StandardMethods\Verification;

    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\CaptchaManager;
    use Socialbox\Managers\SessionManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class VerificationAnswerImageCaptcha extends Method
    {

        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(!$rpcRequest->containsParameter('answer'))
            {
                throw new MissingRpcArgumentException('answer');
            }

            try
            {
                $session = $request->getSession();
                if(CaptchaManager::getCaptcha($session->getPeerUuid())?->isExpired())
                {
                    return $rpcRequest->produceError(StandardError::EXPIRED, 'The captcha has expired');
                }
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException("There was an unexpected error while trying to get the captcha", StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            try
            {
                $result = CaptchaManager::answerCaptcha($session->getPeerUuid(), (string)$rpcRequest->getParameter('answer'));

                if($result)
                {
                    SessionManager::updateFlow($session, [SessionFlags::VER_IMAGE_CAPTCHA]);
                }
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException("There was an unexpected error while trying to answer the captcha", StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse($result);
        }
    }