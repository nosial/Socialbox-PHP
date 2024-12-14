<?php

    namespace Socialbox\Enums;

    use Socialbox\Classes\StandardMethods\AcceptPrivacyPolicy;
    use Socialbox\Classes\StandardMethods\AcceptTermsOfService;
    use Socialbox\Classes\StandardMethods\GetPrivacyPolicy;
    use Socialbox\Classes\StandardMethods\GetSessionState;
    use Socialbox\Classes\StandardMethods\GetTermsOfService;
    use Socialbox\Classes\StandardMethods\Ping;
    use Socialbox\Classes\StandardMethods\SettingsSetPassword;
    use Socialbox\Classes\StandardMethods\VerificationAnswerImageCaptcha;
    use Socialbox\Classes\StandardMethods\VerificationGetImageCaptcha;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    enum StandardMethods : string
    {
        case PING = 'ping';
        case GET_SESSION_STATE = 'getSessionState';
        
        case GET_PRIVACY_POLICY = 'getPrivacyPolicy';
        case ACCEPT_PRIVACY_POLICY = 'acceptPrivacyPolicy';
        case GET_TERMS_OF_SERVICE = 'getTermsOfService';
        case ACCEPT_TERMS_OF_SERVICE = 'acceptTermsOfService';

        case VERIFICATION_GET_IMAGE_CAPTCHA = 'verificationGetImageCaptcha';
        case VERIFICATION_ANSWER_IMAGE_CAPTCHA = 'verificationAnswerImageCaptcha';

        case SETTINGS_SET_PASSWORD = 'settingsSetPassword';

        /**
         * Executes the appropriate operation based on the current context and requests provided.
         *
         * @param ClientRequest $request The client request object containing necessary data for execution.
         * @param RpcRequest $rpcRequest The RPC request object providing additional parameters for execution.
         * @return SerializableInterface|null The result of the operation as a serializable interface or null if no operation matches.
         * @throws StandardException If an error occurs during execution
         */
        public function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            return match ($this)
            {
                self::PING => Ping::execute($request, $rpcRequest),
                self::GET_SESSION_STATE => GetSessionState::execute($request, $rpcRequest),
                
                self::GET_PRIVACY_POLICY => GetPrivacyPolicy::execute($request, $rpcRequest),
                self::ACCEPT_PRIVACY_POLICY => AcceptPrivacyPolicy::execute($request, $rpcRequest),
                self::GET_TERMS_OF_SERVICE => GetTermsOfService::execute($request, $rpcRequest),
                self::ACCEPT_TERMS_OF_SERVICE => AcceptTermsOfService::execute($request, $rpcRequest),

                self::VERIFICATION_GET_IMAGE_CAPTCHA => VerificationGetImageCaptcha::execute($request, $rpcRequest),
                self::VERIFICATION_ANSWER_IMAGE_CAPTCHA => VerificationAnswerImageCaptcha::execute($request, $rpcRequest),

                self::SETTINGS_SET_PASSWORD => SettingsSetPassword::execute($request, $rpcRequest),
            };
        }

        /**
         * Checks if the access method is allowed for the given client request.
         *
         * @param ClientRequest $clientRequest The client request instance to check access against.
         * @return void
         * @throws StandardException If the method is not allowed for the given client request.
         */
        public function checkAccess(ClientRequest $clientRequest): void
        {
            if(in_array($this, self::getAllowedMethods($clientRequest)))
            {
                return;
            }

            throw new StandardException(StandardError::METHOD_NOT_ALLOWED->getMessage(), StandardError::METHOD_NOT_ALLOWED);
        }

        /**
         * Determines the list of allowed methods for a given client request.
         *
         * @param ClientRequest $clientRequest The client request for which allowed methods are determined.
         * @return array Returns an array of allowed methods for the provided client request.
         */
        public static function getAllowedMethods(ClientRequest $clientRequest): array
        {
            $methods = [
                self::PING,
                self::GET_SESSION_STATE,
                self::GET_PRIVACY_POLICY,
                self::GET_TERMS_OF_SERVICE,
            ];

            $session = $clientRequest->getSession();

            if(in_array(SessionFlags::VER_PRIVACY_POLICY, $session->getFlags()))
            {
                $methods[] = self::ACCEPT_PRIVACY_POLICY;
            }

            if(in_array(SessionFlags::VER_TERMS_OF_SERVICE, $session->getFlags()))
            {
                $methods[] = self::ACCEPT_TERMS_OF_SERVICE;
            }

            if(in_array(SessionFlags::VER_IMAGE_CAPTCHA, $session->getFlags()))
            {
                $methods[] = self::VERIFICATION_GET_IMAGE_CAPTCHA;
                $methods[] = self::VERIFICATION_ANSWER_IMAGE_CAPTCHA;
            }

            if(in_array(SessionFlags::SET_PASSWORD, $session->getFlags()))
            {
                $methods[] = self::SETTINGS_SET_PASSWORD;
            }

            return $methods;
        }
    }