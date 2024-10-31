<?php

namespace Socialbox\Classes\StandardMethods;

use Gregwar\Captcha\CaptchaBuilder;
use Socialbox\Abstracts\Method;
use Socialbox\Classes\Logger;
use Socialbox\Enums\Flags\PeerFlags;
use Socialbox\Enums\StandardError;
use Socialbox\Exceptions\DatabaseOperationException;
use Socialbox\Exceptions\StandardException;
use Socialbox\Interfaces\SerializableInterface;
use Socialbox\Managers\CaptchaManager;
use Socialbox\Managers\RegisteredPeerManager;
use Socialbox\Managers\SessionManager;
use Socialbox\Objects\ClientRequest;
use Socialbox\Objects\RpcRequest;
use Socialbox\Objects\Standard\ImageCaptcha;

class GetCaptcha extends Method
{
    /**
     * @inheritDoc
     */
    public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
    {
        // Check if the request has a Session UUID
        if($request->getSessionUuid() === null)
        {
            return $rpcRequest->produceError(StandardError::SESSION_REQUIRED);
        }

        // Get the session and check if it's already authenticated
        try
        {
            $session = SessionManager::getSession($request->getSessionUuid());
        }
        catch(DatabaseOperationException $e)
        {
            throw new StandardException("There was an unexpected error while trying to get the session", StandardError::INTERNAL_SERVER_ERROR, $e);
        }

        // Check for session conditions
        if($session->getPeerUuid() === null)
        {
            return $rpcRequest->produceError(StandardError::AUTHENTICATION_REQUIRED);
        }

        // Get the peer
        try
        {
            $peer = RegisteredPeerManager::getPeer($session->getPeerUuid());
        }
        catch(DatabaseOperationException $e)
        {
            throw new StandardException("There was unexpected error while trying to get the peer", StandardError::INTERNAL_SERVER_ERROR, $e);
        }

        // Check if the VER_SOLVE_IMAGE_CAPTCHA flag exists.
        if(!$peer->flagExists(PeerFlags::VER_SOLVE_IMAGE_CAPTCHA))
        {
            return $rpcRequest->produceError(StandardError::CAPTCHA_NOT_AVAILABLE, 'You are not required to complete a captcha at this time');
        }

        try
        {
            Logger::getLogger()->debug('Creating a new captcha for peer ' . $peer->getUuid());
            $answer = CaptchaManager::createCaptcha($peer);
            $captchaRecord = CaptchaManager::getCaptcha($peer);
        }
        catch (DatabaseOperationException $e)
        {
            throw new StandardException("There was an unexpected error while trying create the captcha", StandardError::INTERNAL_SERVER_ERROR, $e);
        }

        // Build the captcha
        Logger::getLogger()->debug('Building captcha for peer ' . $peer->getUuid());
        return $rpcRequest->produceResponse(new ImageCaptcha([
            'expires' => $captchaRecord->getExpires()->getTimestamp(),
            'image' => (new CaptchaBuilder($answer))->build()->inline()] // Returns HTML base64 encoded image of the captcha
        ));
    }
}