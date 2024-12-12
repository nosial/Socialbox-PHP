<?php

namespace Socialbox\Classes\StandardMethods;

use Socialbox\Abstracts\Method;
use Socialbox\Enums\Flags\PeerFlags;
use Socialbox\Enums\StandardError;
use Socialbox\Exceptions\DatabaseOperationException;
use Socialbox\Exceptions\StandardException;
use Socialbox\Interfaces\SerializableInterface;
use Socialbox\Managers\CaptchaManager;
use Socialbox\Managers\RegisteredPeerManager;
use Socialbox\Managers\SessionManager;
use Socialbox\Objects\ClientRequestOld;
use Socialbox\Objects\RpcRequest;

class VerificationAnswerImageCaptcha extends Method
{

    /**
     * @inheritDoc
     */
    public static function execute(ClientRequestOld $request, RpcRequest $rpcRequest): ?SerializableInterface
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

        if(!$rpcRequest->containsParameter('answer'))
        {
            return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'The answer parameter is required');
        }

        try
        {
            if(CaptchaManager::getCaptcha($session->getPeerUuid())->isExpired())
            {
                return $rpcRequest->produceError(StandardError::CAPTCHA_EXPIRED, 'The captcha has expired');
            }
        }
        catch(DatabaseOperationException $e)
        {
            throw new StandardException("There was an unexpected error while trying to get the captcha", StandardError::INTERNAL_SERVER_ERROR, $e);
        }

        try
        {
            $result = CaptchaManager::answerCaptcha($session->getPeerUuid(), $rpcRequest->getParameter('answer'));

            if($result)
            {
                RegisteredPeerManager::removeFlag($session->getPeerUuid(), PeerFlags::VER_SOLVE_IMAGE_CAPTCHA);
            }

            return $rpcRequest->produceResponse($result);
        }
        catch (DatabaseOperationException $e)
        {
            throw new StandardException("There was an unexpected error while trying to answer the captcha", StandardError::INTERNAL_SERVER_ERROR, $e);
        }
    }
}