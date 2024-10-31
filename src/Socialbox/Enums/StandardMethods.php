<?php

namespace Socialbox\Enums;

use Socialbox\Classes\StandardMethods\CreateSession;
use Socialbox\Classes\StandardMethods\VerificationAnswerImageCaptcha;
use Socialbox\Classes\StandardMethods\VerificationGetImageCaptcha;
use Socialbox\Classes\StandardMethods\GetMe;
use Socialbox\Classes\StandardMethods\Ping;
use Socialbox\Classes\StandardMethods\Register;
use Socialbox\Exceptions\StandardException;
use Socialbox\Interfaces\SerializableInterface;
use Socialbox\Objects\ClientRequest;
use Socialbox\Objects\RpcRequest;

enum StandardMethods : string
{
    case PING = 'ping';
    case CREATE_SESSION = 'createSession';
    case REGISTER = 'register';
    case GET_ME = 'getMe';
    case VERIFICATION_GET_IMAGE_CAPTCHA = 'verificationGetImageCaptcha';
    case VERIFICATION_ANSWER_IMAGE_CAPTCHA = 'verificationAnswerImageCaptcha';

    /**
     * @param ClientRequest $request
     * @param RpcRequest $rpcRequest
     * @return SerializableInterface|null
     * @throws StandardException
     */
    public function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
    {
        return match ($this)
        {
            self::PING => Ping::execute($request, $rpcRequest),
            self::CREATE_SESSION => CreateSession::execute($request, $rpcRequest),
            self::REGISTER => Register::execute($request, $rpcRequest),
            self::GET_ME => GetMe::execute($request, $rpcRequest),
            self::VERIFICATION_GET_IMAGE_CAPTCHA => VerificationGetImageCaptcha::execute($request, $rpcRequest),
            self::VERIFICATION_ANSWER_IMAGE_CAPTCHA => VerificationAnswerImageCaptcha::execute($request, $rpcRequest),
        };
    }
}