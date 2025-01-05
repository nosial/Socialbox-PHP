<?php

    namespace Socialbox\Enums;

    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\StandardMethods\AcceptCommunityGuidelines;
    use Socialbox\Classes\StandardMethods\AcceptPrivacyPolicy;
    use Socialbox\Classes\StandardMethods\AcceptTermsOfService;
    use Socialbox\Classes\StandardMethods\GetCommunityGuidelines;
    use Socialbox\Classes\StandardMethods\GetPrivacyPolicy;
    use Socialbox\Classes\StandardMethods\GetSessionState;
    use Socialbox\Classes\StandardMethods\GetTermsOfService;
    use Socialbox\Classes\StandardMethods\Ping;
    use Socialbox\Classes\StandardMethods\SettingsAddSigningKey;
    use Socialbox\Classes\StandardMethods\SettingsDeleteDisplayName;
    use Socialbox\Classes\StandardMethods\SettingsDeletePassword;
    use Socialbox\Classes\StandardMethods\SettingsGetSigningKeys;
    use Socialbox\Classes\StandardMethods\SettingsSetDisplayName;
    use Socialbox\Classes\StandardMethods\SettingsSetPassword;
    use Socialbox\Classes\StandardMethods\SettingsUpdatePassword;
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
        case GET_COMMUNITY_GUIDELINES = 'getCommunityGuidelines';
        case ACCEPT_COMMUNITY_GUIDELINES = 'acceptCommunityGuidelines';

        case VERIFICATION_EMAIL = 'verificationEmail';
        case VERIFICATION_ANSWER_EMAIL = 'verificationAnswerEmail';

        case VERIFICATION_SMS = 'verificationSms';
        case VERIFICATION_ANSWER_SMS = 'verificationAnswerSms';

        case VERIFICATION_PHONE_CALL = 'verificationPhoneCall';
        case VERIFICATION_ANSWER_PHONE_CALL = 'verificationAnswerPhoneCall';

        case VERIFICATION_GET_IMAGE_CAPTCHA = 'verificationGetImageCaptcha';
        case VERIFICATION_ANSWER_IMAGE_CAPTCHA = 'verificationAnswerImageCaptcha';

        case VERIFICATION_GET_TEXT_CAPTCHA = 'verificationGetTextCaptcha';
        case VERIFICATION_ANSWER_TEXT_CAPTCHA = 'verificationAnswerTextCaptcha';

        case VERIFICATION_GET_EXTERNAL_URL = 'verificationGetExternalUrl';
        case VERIFICATION_ANSWER_EXTERNAL_URL = 'verificationAnswerExternalUrl';

        case SETTINGS_SET_PASSWORD = 'settingsSetPassword';
        case SETTINGS_UPDATE_PASSWORD = 'settingsUpdatePassword';
        case SETTINGS_DELETE_PASSWORD = 'settingsDeletePassword';
        case SETTINGS_SET_OTP = 'settingsSetOtp';
        case SETTINGS_SET_DISPLAY_NAME = 'settingsSetDisplayName';
        case SETTINGS_DELETE_DISPLAY_NAME = 'settingsDeleteDisplayName';
        case SETTINGS_SET_DISPLAY_PICTURE = 'settingsSetDisplayPicture';
        case SETTINGS_SET_EMAIL = 'settingsSetEmail';
        case SETTINGS_SET_PHONE = 'settingsSetPhone';
        case SETTINGS_SET_BIRTHDAY = 'settingsSetBirthday';

        case SETTINGS_ADD_SIGNING_KEY = 'settingsAddSigningKey';
        case SETTINGS_GET_SIGNING_KEYS = 'settingsGetSigningKeys';

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
                self::GET_COMMUNITY_GUIDELINES => GetCommunityGuidelines::execute($request, $rpcRequest),
                self::ACCEPT_COMMUNITY_GUIDELINES => AcceptCommunityGuidelines::execute($request, $rpcRequest),

                self::VERIFICATION_GET_IMAGE_CAPTCHA => VerificationGetImageCaptcha::execute($request, $rpcRequest),
                self::VERIFICATION_ANSWER_IMAGE_CAPTCHA => VerificationAnswerImageCaptcha::execute($request, $rpcRequest),

                self::SETTINGS_SET_PASSWORD => SettingsSetPassword::execute($request, $rpcRequest),
                self::SETTINGS_UPDATE_PASSWORD => SettingsUpdatePassword::execute($request, $rpcRequest),
                self::SETTINGS_DELETE_PASSWORD => SettingsDeletePassword::execute($request, $rpcRequest),
                self::SETTINGS_SET_DISPLAY_NAME => SettingsSetDisplayName::execute($request, $rpcRequest),
                self::SETTINGS_DELETE_DISPLAY_NAME => SettingsDeleteDisplayName::execute($request, $rpcRequest),

                self::SETTINGS_ADD_SIGNING_KEY => SettingsAddSigningKey::execute($request, $rpcRequest),
                self::SETTINGS_GET_SIGNING_KEYS => SettingsGetSigningKeys::execute($request, $rpcRequest),

                default => $rpcRequest->produceError(StandardError::METHOD_NOT_ALLOWED, sprintf("The method %s is not supported by the server", $rpcRequest->getMethod()))
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
            // These methods should always accessible
            $methods = [
                // Important methods
                self::PING, // Always allow the ping method
                self::GET_SESSION_STATE, // The session state should always be accessible
                self::GET_PRIVACY_POLICY, // The user should always be able to get the privacy policy
                self::GET_TERMS_OF_SERVICE, // The user should always be able to get the terms of service
                self::GET_COMMUNITY_GUIDELINES, // The user should always be able to get the community guidelines
            ];

            $session = $clientRequest->getSession();

            // If the session is external (eg; coming from a different server)
            // Servers will have their own access control mechanisms
            if($session->isExternal())
            {
                // TODO: Implement server access control
            }
            // If the session is authenticated, then allow additional method calls
            elseif($session->isAuthenticated())
            {
                // These methods are always allowed for authenticated users
                $methods = array_merge($methods, [
                    self::SETTINGS_ADD_SIGNING_KEY,
                    self::SETTINGS_GET_SIGNING_KEYS,
                    self::SETTINGS_SET_DISPLAY_NAME,
                    self::SETTINGS_SET_PASSWORD,
                ]);

                // Prevent the user from deleting their display name if it is required
                if(!Configuration::getRegistrationConfiguration()->isDisplayNameRequired())
                {
                    $methods[] = self::SETTINGS_DELETE_DISPLAY_NAME;
                }

                // Always allow the authenticated user to change their password
                if(!in_array(SessionFlags::SET_PASSWORD, $session->getFlags()))
                {
                    $methods[] = self::SETTINGS_SET_PASSWORD;
                }
            }
            // If the session isn't authenticated nor a host, a limited set of methods is available
            else
            {
                // If the flag `VER_PRIVACY_POLICY` is set, then the user can accept the privacy policy
                if($session->flagExists(SessionFlags::VER_PRIVACY_POLICY))
                {
                    $methods[] = self::ACCEPT_PRIVACY_POLICY;
                }

                // If the flag `VER_TERMS_OF_SERVICE` is set, then the user can accept the terms of service
                if($session->flagExists(SessionFlags::VER_TERMS_OF_SERVICE))
                {
                    $methods[] = self::ACCEPT_TERMS_OF_SERVICE;
                }

                // If the flag `VER_COMMUNITY_GUIDELINES` is set, then the user can accept the community guidelines
                if($session->flagExists(SessionFlags::VER_COMMUNITY_GUIDELINES))
                {
                    $methods[] = self::ACCEPT_COMMUNITY_GUIDELINES;
                }

                // If the flag `VER_IMAGE_CAPTCHA` is set, then the user has to get and answer an image captcha
                if($session->flagExists(SessionFlags::VER_IMAGE_CAPTCHA))
                {
                    $methods[] = self::VERIFICATION_GET_IMAGE_CAPTCHA;
                    $methods[] = self::VERIFICATION_ANSWER_IMAGE_CAPTCHA;
                }

                // If the flag `SET_PASSWORD` is set, then the user has to set a password
                if($session->flagExists(SessionFlags::SET_PASSWORD))
                {
                    $methods[] = self::SETTINGS_SET_PASSWORD;
                }

                // If the flag `SET_DISPLAY_NAME` is set, then the user has to set a display name
                if($session->flagExists(SessionFlags::SET_DISPLAY_NAME))
                {
                    $methods[] = self::SETTINGS_SET_DISPLAY_NAME;
                }
            }

            return $methods;
        }
    }