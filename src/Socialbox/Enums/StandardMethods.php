<?php

    namespace Socialbox\Enums;

    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\StandardMethods\AcceptCommunityGuidelines;
    use Socialbox\Classes\StandardMethods\AcceptPrivacyPolicy;
    use Socialbox\Classes\StandardMethods\AcceptTermsOfService;
    use Socialbox\Classes\StandardMethods\AddressBookAddContact;
    use Socialbox\Classes\StandardMethods\AddressBookDeleteContact;
    use Socialbox\Classes\StandardMethods\AddressBookGetContacts;
    use Socialbox\Classes\StandardMethods\Authenticate;
    use Socialbox\Classes\StandardMethods\GetAllowedMethods;
    use Socialbox\Classes\StandardMethods\GetCommunityGuidelines;
    use Socialbox\Classes\StandardMethods\GetPrivacyPolicy;
    use Socialbox\Classes\StandardMethods\GetSessionState;
    use Socialbox\Classes\StandardMethods\GetTermsOfService;
    use Socialbox\Classes\StandardMethods\Ping;
    use Socialbox\Classes\StandardMethods\ResolvePeer;
    use Socialbox\Classes\StandardMethods\SettingsAddSigningKey;
    use Socialbox\Classes\StandardMethods\SettingsDeleteBirthday;
    use Socialbox\Classes\StandardMethods\SettingsDeleteDisplayName;
    use Socialbox\Classes\StandardMethods\SettingsDeleteDisplayPicture;
    use Socialbox\Classes\StandardMethods\SettingsDeleteEmailAddress;
    use Socialbox\Classes\StandardMethods\SettingsDeletePassword;
    use Socialbox\Classes\StandardMethods\SettingsDeletePhoneNumber;
    use Socialbox\Classes\StandardMethods\SettingsGetSigningKeys;
    use Socialbox\Classes\StandardMethods\SettingsSetBirthday;
    use Socialbox\Classes\StandardMethods\SettingsSetDisplayName;
    use Socialbox\Classes\StandardMethods\SettingsSetDisplayPicture;
    use Socialbox\Classes\StandardMethods\SettingsSetEmailAddress;
    use Socialbox\Classes\StandardMethods\SettingsSetPassword;
    use Socialbox\Classes\StandardMethods\SettingsSetPhoneNumber;
    use Socialbox\Classes\StandardMethods\SettingsUpdatePassword;
    use Socialbox\Classes\StandardMethods\VerificationAnswerImageCaptcha;
    use Socialbox\Classes\StandardMethods\VerificationGetImageCaptcha;
    use Socialbox\Classes\StandardMethods\VerificationOtpAuthentication;
    use Socialbox\Classes\StandardMethods\VerificationPasswordAuthentication;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\OneTimePasswordManager;
    use Socialbox\Managers\PasswordManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\Database\SessionRecord;
    use Socialbox\Objects\RpcRequest;

    enum StandardMethods : string
    {
        case PING = 'ping';
        case GET_SESSION_STATE = 'getSessionState';
        case GET_ALLOWED_METHODS = 'getAllowedMethods';
        
        case GET_PRIVACY_POLICY = 'getPrivacyPolicy';
        case ACCEPT_PRIVACY_POLICY = 'acceptPrivacyPolicy';
        case GET_TERMS_OF_SERVICE = 'getTermsOfService';
        case ACCEPT_TERMS_OF_SERVICE = 'acceptTermsOfService';
        case GET_COMMUNITY_GUIDELINES = 'getCommunityGuidelines';
        case ACCEPT_COMMUNITY_GUIDELINES = 'acceptCommunityGuidelines';

        case VERIFICATION_EMAIL = 'verificationEmail'; // NOT IMPLEMENTED
        case VERIFICATION_ANSWER_EMAIL = 'verificationAnswerEmail'; // NOT IMPLEMENTED

        case VERIFICATION_SMS = 'verificationSms'; // NOT IMPLEMENTED
        case VERIFICATION_ANSWER_SMS = 'verificationAnswerSms'; // NOT IMPLEMENTED

        case VERIFICATION_PHONE_CALL = 'verificationPhoneCall'; // NOT IMPLEMENTED
        case VERIFICATION_ANSWER_PHONE_CALL = 'verificationAnswerPhoneCall'; // NOT IMPLEMENTED

        case VERIFICATION_GET_IMAGE_CAPTCHA = 'verificationGetImageCaptcha';
        case VERIFICATION_ANSWER_IMAGE_CAPTCHA = 'verificationAnswerImageCaptcha';

        case VERIFICATION_GET_TEXT_CAPTCHA = 'verificationGetTextCaptcha'; // NOT IMPLEMENTED
        case VERIFICATION_ANSWER_TEXT_CAPTCHA = 'verificationAnswerTextCaptcha'; // NOT IMPLEMENTED

        case VERIFICATION_GET_EXTERNAL_URL = 'verificationGetExternalUrl'; // NOT IMPLEMENTED
        case VERIFICATION_ANSWER_EXTERNAL_URL = 'verificationAnswerExternalUrl'; // NOT IMPLEMENTED
        
        case VERIFICATION_PASSWORD_AUTHENTICATION = 'verificationPasswordAuthentication';
        case VERIFICATION_OTP_AUTHENTICATION = 'verificationOtpAuthentication';

        case SETTINGS_SET_PASSWORD = 'settingsSetPassword';
        case SETTINGS_UPDATE_PASSWORD = 'settingsUpdatePassword';
        case SETTINGS_DELETE_PASSWORD = 'settingsDeletePassword';
        case SETTINGS_SET_OTP = 'settingsSetOtp';
        case SETTINGS_DELETE_OTP = 'settingsDeleteOtp';
        case SETTINGS_SET_DISPLAY_NAME = 'settingsSetDisplayName';
        case SETTINGS_DELETE_DISPLAY_NAME = 'settingsDeleteDisplayName';
        case SETTINGS_SET_DISPLAY_PICTURE = 'settingsSetDisplayPicture';
        case SETTINGS_DELETE_DISPLAY_PICTURE = 'settingsDeleteDisplayPicture';
        case SETTINGS_SET_EMAIL = 'settingsSetEmail';
        case SETTINGS_DELETE_EMAIL = 'settingsDeleteEmail';
        case SETTINGS_SET_PHONE = 'settingsSetPhone';
        case SETTINGS_DELETE_PHONE = 'settingsDeletePhone';
        case SETTINGS_SET_BIRTHDAY = 'settingsSetBirthday';
        case SETTINGS_DELETE_BIRTHDAY = 'settingsDeleteBirthday';

        case SETTINGS_ADD_SIGNING_KEY = 'settingsAddSigningKey';
        case SETTINGS_GET_SIGNING_KEYS = 'settingsGetSigningKeys';

        case ADDRESS_BOOK_ADD_CONTACT = 'addressBookAddContact';
        case ADDRESS_BOOK_DELETE_CONTACT = 'addressBookDeleteContact';
        case ADDRESS_BOOK_GET_CONTACTS = 'addressBookGetContacts';

        case AUTHENTICATE = 'authenticate';
        case RESOLVE_PEER = 'resolvePeer';

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
                self::GET_ALLOWED_METHODS => GetAllowedMethods::execute($request, $rpcRequest),
                
                self::GET_PRIVACY_POLICY => GetPrivacyPolicy::execute($request, $rpcRequest),
                self::ACCEPT_PRIVACY_POLICY => AcceptPrivacyPolicy::execute($request, $rpcRequest),
                self::GET_TERMS_OF_SERVICE => GetTermsOfService::execute($request, $rpcRequest),
                self::ACCEPT_TERMS_OF_SERVICE => AcceptTermsOfService::execute($request, $rpcRequest),
                self::GET_COMMUNITY_GUIDELINES => GetCommunityGuidelines::execute($request, $rpcRequest),
                self::ACCEPT_COMMUNITY_GUIDELINES => AcceptCommunityGuidelines::execute($request, $rpcRequest),

                self::VERIFICATION_GET_IMAGE_CAPTCHA => VerificationGetImageCaptcha::execute($request, $rpcRequest),
                self::VERIFICATION_ANSWER_IMAGE_CAPTCHA => VerificationAnswerImageCaptcha::execute($request, $rpcRequest),
                
                self::VERIFICATION_PASSWORD_AUTHENTICATION => VerificationPasswordAuthentication::execute($request, $rpcRequest),
                self::VERIFICATION_OTP_AUTHENTICATION => VerificationOtpAuthentication::execute($request, $rpcRequest),

                self::SETTINGS_SET_PASSWORD => SettingsSetPassword::execute($request, $rpcRequest),
                self::SETTINGS_UPDATE_PASSWORD => SettingsUpdatePassword::execute($request, $rpcRequest),
                self::SETTINGS_DELETE_PASSWORD => SettingsDeletePassword::execute($request, $rpcRequest),
                self::SETTINGS_SET_DISPLAY_NAME => SettingsSetDisplayName::execute($request, $rpcRequest),
                self::SETTINGS_DELETE_DISPLAY_NAME => SettingsDeleteDisplayName::execute($request, $rpcRequest),
                self::SETTINGS_SET_DISPLAY_PICTURE => SettingsSetDisplayPicture::execute($request, $rpcRequest),
                self::SETTINGS_DELETE_DISPLAY_PICTURE => SettingsDeleteDisplayPicture::execute($request, $rpcRequest),
                self::SETTINGS_SET_EMAIL => SettingsSetEmailAddress::execute($request, $rpcRequest),
                self::SETTINGS_DELETE_EMAIL => SettingsDeleteEmailAddress::execute($request, $rpcRequest),
                self::SETTINGS_SET_PHONE => SettingsSetPhoneNumber::execute($request, $rpcRequest),
                self::SETTINGS_DELETE_PHONE => SettingsDeletePhoneNumber::execute($request, $rpcRequest),
                self::SETTINGS_SET_BIRTHDAY => SettingsSetBirthday::execute($request, $rpcRequest),
                self::SETTINGS_DELETE_BIRTHDAY  => SettingsDeleteBirthday::execute($request, $rpcRequest),

                self::SETTINGS_ADD_SIGNING_KEY => SettingsAddSigningKey::execute($request, $rpcRequest),
                self::SETTINGS_GET_SIGNING_KEYS => SettingsGetSigningKeys::execute($request, $rpcRequest),

                self::ADDRESS_BOOK_ADD_CONTACT => AddressBookAddContact::execute($request, $rpcRequest),
                self::ADDRESS_BOOK_DELETE_CONTACT => AddressBookDeleteContact::execute($request, $rpcRequest),
                self::ADDRESS_BOOK_GET_CONTACTS => AddressBookGetContacts::execute($request, $rpcRequest),

                self::AUTHENTICATE => Authenticate::execute($request, $rpcRequest),
                self::RESOLVE_PEER => ResolvePeer::execute($request, $rpcRequest),

                default => $rpcRequest->produceError(StandardError::METHOD_NOT_ALLOWED, sprintf("The method %s is not supported by the server", $rpcRequest->getMethod()))
            };
        }

        /**
         * Checks if the access method is allowed for the given client request.
         *
         * @param ClientRequest $clientRequest The client request instance to check access against.
         * @return void
         * @throws DatabaseOperationException If an error occurs while checking the database for session information.
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
         * @throws DatabaseOperationException If an error occurs while checking the database for session information.
         */
        public static function getAllowedMethods(ClientRequest $clientRequest): array
        {
            // These methods should always accessible
            $methods = [
                // Important methods
                self::PING, // Always allow the ping method
                self::GET_SESSION_STATE, // The session state should always be accessible
                self::GET_ALLOWED_METHODS, // Client should always be able to get the allowed methods
                self::GET_PRIVACY_POLICY, // The user should always be able to get the privacy policy
                self::GET_TERMS_OF_SERVICE, // The user should always be able to get the terms of service
                self::GET_COMMUNITY_GUIDELINES, // The user should always be able to get the community guidelines
            ];

            $session = $clientRequest->getSession();

            if($session === null)
            {
                return $methods;
            }

            try
            {
                $external = $session->isExternal();
            }
            catch(DatabaseOperationException)
            {
                $external = false;
            }

            // If the session is external (eg; coming from a different server)
            // Servers will have their own access control mechanisms
            if($external)
            {
                $methods = array_merge($methods, self::getExternalMethods($clientRequest));
            }
            // If the session is authenticated, then allow additional method calls
            elseif($session->isAuthenticated())
            {
                $methods = array_merge($methods, self::getAuthenticatedMethods());
            }
            // If the session isn't authenticated, check if it's a registering user
            elseif($session->flagExists(SessionFlags::REGISTRATION_REQUIRED))
            {
                $methods = array_merge($methods, self::getRegistrationMethods($session));
            }
            // If the user is a registering peer, check if it's an authenticating one
            elseif($session->flagExists(SessionFlags::AUTHENTICATION_REQUIRED))
            {
                $methods = array_merge($methods, self::getAuthenticationMethods($clientRequest));
            }

            return $methods;
        }

        /**
         * Retrieves a list of external methods based on the client's session state.
         *
         * @param ClientRequest $clientRequest The client request object containing all the request parameters
         * @return array Returns an array methods that are available for external sessions
         */
        private static function getExternalMethods(ClientRequest $clientRequest): array
        {
            $methods = [];

            $session = $clientRequest->getSession();
            if(!$session->isAuthenticated() || $session->flagExists(SessionFlags::AUTHENTICATION_REQUIRED))
            {
                $methods[] = self::AUTHENTICATE;
            }
            else
            {
                $methods[] = self::RESOLVE_PEER;
            }

            return $methods;
        }

        /**
         * Retrieves a list of authenticated user methods based on configuration settings.
         *
         * @return array An array of methods that are available to
         */
        private static function getAuthenticatedMethods(): array
        {

            // These methods are always allowed for authenticated users
            $methods = [
                self::SETTINGS_ADD_SIGNING_KEY,
                self::SETTINGS_GET_SIGNING_KEYS,
                self::SETTINGS_SET_DISPLAY_NAME,
                self::SETTINGS_SET_DISPLAY_PICTURE,
                self::SETTINGS_SET_PASSWORD,
                self::SETTINGS_UPDATE_PASSWORD,
                self::SETTINGS_SET_OTP,
                self::SETTINGS_SET_EMAIL,
                self::SETTINGS_SET_PHONE,
                self::SETTINGS_SET_BIRTHDAY,
                self::RESOLVE_PEER,

                self::ADDRESS_BOOK_ADD_CONTACT,
                self::ADDRESS_BOOK_DELETE_CONTACT,
                self::ADDRESS_BOOK_GET_CONTACTS,
            ];

            // Prevent the user from deleting their display name if it is required
            if(!Configuration::getRegistrationConfiguration()->isDisplayNameRequired())
            {
                $methods[] = self::SETTINGS_DELETE_DISPLAY_NAME;
            }

            // Prevent the user from deleting their password if it is required
            if(!Configuration::getRegistrationConfiguration()->isPasswordRequired())
            {
                $methods[] = self::SETTINGS_DELETE_PASSWORD;
            }

            // Prevent the user from deleting their display picture if it is required
            if(!Configuration::getRegistrationConfiguration()->isDisplayPictureRequired())
            {
                $methods[] = self::SETTINGS_DELETE_DISPLAY_PICTURE;
            }

            // Prevent the user from deleting their OTP if it is required
            if(!Configuration::getRegistrationConfiguration()->isOtpRequired())
            {
                $methods[] = self::SETTINGS_DELETE_OTP;
            }

            // Prevent the user from deleting their Phone Number if it is required
            if(!Configuration::getRegistrationConfiguration()->isPhoneNumberRequired())
            {
                $methods[] = self::SETTINGS_DELETE_PHONE;
            }

            // Prevent the user from deleting their email address if it is required
            if(!Configuration::getRegistrationConfiguration()->isEmailAddressRequired())
            {
                $methods[] = self::SETTINGS_DELETE_EMAIL;
            }

            // Prevent the user from deleting their birthday if it is required
            if(!Configuration::getRegistrationConfiguration()->isBirthdayRequired())
            {
                $methods[] = self::SETTINGS_DELETE_BIRTHDAY;
            }

            return $methods;
        }

        /**
         * Retrieves a list of registration methods based on the session flags.
         *
         * @param SessionRecord $session The session record containing flags that determine available registration methods.
         * @return array An array of registration methods available for the session.
         */
        private static function getRegistrationMethods(SessionRecord $session): array
        {
            // Don't allow registration methods if registration is disabled
            if(!Configuration::getRegistrationConfiguration()->isRegistrationEnabled())
            {
                return [];
            }

            $methods = [];

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

            // If the flag `SET_OTP` is set, then the user has to set an OTP
            if($session->flagExists(SessionFLags::SET_OTP))
            {
                $methods[] = self::SETTINGS_SET_OTP;
            }

            // If the flag `SET_DISPLAY_NAME` is set, then the user has to set a display name
            if($session->flagExists(SessionFlags::SET_DISPLAY_NAME))
            {
                $methods[] = self::SETTINGS_SET_DISPLAY_NAME;
            }

            // If the flag `SET_DISPLAY_PICTURE` is set, then the user has to set a display picture
            if($session->flagExists(SessionFlags::SET_DISPLAY_PICTURE))
            {
                $methods[] = self::SETTINGS_SET_DISPLAY_PICTURE;
            }

            // If the flag `SET_EMAIL` is set, then the user has to set an email address
            if($session->flagExists(SessionFlags::SET_EMAIL))
            {
                $methods[] = self::SETTINGS_SET_EMAIL;
            }

            // If the flag `SET_PHONE` is set, then the user has to set a phone number
            if($session->flagExists(SessionFlags::SET_PHONE))
            {
                $methods[] = self::SETTINGS_SET_PHONE;
            }

            // If the flag `SET_BIRTHDAY` is set, then the user has to set a birthday
            if($session->flagExists(SessionFlags::SET_BIRTHDAY))
            {
                $methods[] = self::SETTINGS_SET_BIRTHDAY;
            }

            return $methods;
        }


        /**
         * Retrieves the list of authentication methods available for the given client request.
         *
         * @param ClientRequest $clientRequest The client request for which the authentication methods are determined.
         * @return array The list of available authentication methods as an array of constants.
         * @throws DatabaseOperationException If an error occurs while checking the database for authentication methods.
         */
        private static function getAuthenticationMethods(ClientRequest $clientRequest): array
        {
            if(!Configuration::getAuthenticationConfiguration()->isEnabled())
            {
                return [];
            }

            $methods = [];

            if(Configuration::getAuthenticationConfiguration()->isImageCaptchaVerificationRequired())
            {
                $methods[] = self::VERIFICATION_GET_IMAGE_CAPTCHA;
                $methods[] = self::VERIFICATION_ANSWER_IMAGE_CAPTCHA;
            }


            $peer = $clientRequest->getPeer();

            if(PasswordManager::usesPassword($peer))
            {
                $methods[] = self::VERIFICATION_PASSWORD_AUTHENTICATION;
            }

            if(OneTimePasswordManager::usesOtp($peer->getUuid()))
            {
                $methods[] = self::VERIFICATION_OTP_AUTHENTICATION;
            }

            return $methods;
        }
    }