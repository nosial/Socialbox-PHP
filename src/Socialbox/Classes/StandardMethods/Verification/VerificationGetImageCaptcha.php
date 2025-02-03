<?php

    namespace Socialbox\Classes\StandardMethods\Verification;

    use Gregwar\Captcha\CaptchaBuilder;
    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\CaptchaManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;
    use Socialbox\Objects\Standard\ImageCaptchaVerification;

    class VerificationGetImageCaptcha extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            try
            {
                $session = $request->getSession();
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('An error occurred while trying to get the session', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            // Check for session conditions
            if(!$session->flagExists(SessionFlags::VER_IMAGE_CAPTCHA))
            {
                return $rpcRequest->produceError(StandardError::METHOD_NOT_ALLOWED, 'Solving an image captcha is not required at this time');
            }

            try
            {
                $peer = $request->getPeer();
                if(CaptchaManager::captchaExists($peer))
                {
                    $captchaRecord = CaptchaManager::getCaptcha($peer);
                    if($captchaRecord->isExpired())
                    {
                        $answer = CaptchaManager::createCaptcha($peer);
                        $captchaRecord = CaptchaManager::getCaptcha($peer);
                    }
                    else
                    {
                        $answer = $captchaRecord->getAnswer();
                    }
                }
                else
                {
                    $answer = CaptchaManager::createCaptcha($peer);
                    $captchaRecord = CaptchaManager::getCaptcha($peer);
                }
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException("There was an unexpected error while trying create the captcha", StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            // Build the captcha
            // Returns HTML base64 encoded image of the captcha
            // Important note: Must always be HTML-BASE64 which means it must be prefixed with `data:image/jpeg;base64,`
            return $rpcRequest->produceResponse(new ImageCaptchaVerification([
                'expires' => $captchaRecord->getExpires(),
                'content' => (new CaptchaBuilder($answer))->build()->inline()
            ]));
        }
    }