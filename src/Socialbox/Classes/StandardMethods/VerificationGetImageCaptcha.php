<?php

    namespace Socialbox\Classes\StandardMethods;

    use Gregwar\Captcha\CaptchaBuilder;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Logger;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\CaptchaManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;
    use Socialbox\Objects\Standard\ImageCaptcha;

    class VerificationGetImageCaptcha extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            $session = $request->getSession();

            // Check for session conditions
            if($session->getPeerUuid() === null)
            {
                return $rpcRequest->produceError(StandardError::AUTHENTICATION_REQUIRED);
            }

            $peer = $request->getPeer();

            try
            {
                Logger::getLogger()->debug('Creating a new captcha for peer ' . $peer->getUuid());
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
                throw new StandardException("There was an unexpected error while trying create the captcha", StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            // Build the captcha
            // Returns HTML base64 encoded image of the captcha
            // Important note: Must always be HTML-BASE64 which means it must be prefixed with `data:image/jpeg;base64,`
            return $rpcRequest->produceResponse(new ImageCaptcha([
                'expires' => $captchaRecord->getExpires(),
                'content' => (new CaptchaBuilder($answer))->build()->inline()
            ]));
        }
    }