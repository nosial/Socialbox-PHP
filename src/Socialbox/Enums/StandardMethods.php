<?php

    namespace Socialbox\Enums;

    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\StandardMethods\AddressBook\AddressBookAddContact;
    use Socialbox\Classes\StandardMethods\AddressBook\AddressBookContactExists;
    use Socialbox\Classes\StandardMethods\AddressBook\AddressBookDeleteContact;
    use Socialbox\Classes\StandardMethods\AddressBook\AddressBookGetContact;
    use Socialbox\Classes\StandardMethods\AddressBook\AddressBookGetContacts;
    use Socialbox\Classes\StandardMethods\AddressBook\AddressBookRevokeSignature;
    use Socialbox\Classes\StandardMethods\AddressBook\AddressBookTrustSignature;
    use Socialbox\Classes\StandardMethods\AddressBook\AddressBookUpdateRelationship;
    use Socialbox\Classes\StandardMethods\Core\GetAllowedMethods;
    use Socialbox\Classes\StandardMethods\Core\GetSelf;
    use Socialbox\Classes\StandardMethods\Core\GetSessionState;
    use Socialbox\Classes\StandardMethods\Core\Ping;
    use Socialbox\Classes\StandardMethods\Core\ResolvePeer;
    use Socialbox\Classes\StandardMethods\Core\ResolveSignature;
    use Socialbox\Classes\StandardMethods\Core\VerifySignature;
    use Socialbox\Classes\StandardMethods\EncryptionChannel\EncryptionAcceptChannel;
    use Socialbox\Classes\StandardMethods\EncryptionChannel\EncryptionChannelAcknowledgeMessage;
    use Socialbox\Classes\StandardMethods\EncryptionChannel\EncryptionChannelExists;
    use Socialbox\Classes\StandardMethods\EncryptionChannel\EncryptionChannelReceive;
    use Socialbox\Classes\StandardMethods\EncryptionChannel\EncryptionChannelRejectMessage;
    use Socialbox\Classes\StandardMethods\EncryptionChannel\EncryptionChannelSend;
    use Socialbox\Classes\StandardMethods\EncryptionChannel\EncryptionCloseChannel;
    use Socialbox\Classes\StandardMethods\EncryptionChannel\EncryptionCreateChannel;
    use Socialbox\Classes\StandardMethods\EncryptionChannel\EncryptionDeclineChannel;
    use Socialbox\Classes\StandardMethods\EncryptionChannel\EncryptionDeleteChannel;
    use Socialbox\Classes\StandardMethods\EncryptionChannel\EncryptionGetChannel;
    use Socialbox\Classes\StandardMethods\EncryptionChannel\EncryptionGetChannelRequests;
    use Socialbox\Classes\StandardMethods\EncryptionChannel\EncryptionGetChannels;
    use Socialbox\Classes\StandardMethods\EncryptionChannel\EncryptionGetIncomingChannels;
    use Socialbox\Classes\StandardMethods\EncryptionChannel\EncryptionGetOutgoingChannels;
    use Socialbox\Classes\StandardMethods\ServerDocuments\AcceptCommunityGuidelines;
    use Socialbox\Classes\StandardMethods\ServerDocuments\AcceptPrivacyPolicy;
    use Socialbox\Classes\StandardMethods\ServerDocuments\AcceptTermsOfService;
    use Socialbox\Classes\StandardMethods\ServerDocuments\GetCommunityGuidelines;
    use Socialbox\Classes\StandardMethods\ServerDocuments\GetPrivacyPolicy;
    use Socialbox\Classes\StandardMethods\ServerDocuments\GetTermsOfService;
    use Socialbox\Classes\StandardMethods\Settings\SettingsAddInformationField;
    use Socialbox\Classes\StandardMethods\Settings\SettingsAddSignature;
    use Socialbox\Classes\StandardMethods\Settings\SettingsDeleteInformationField;
    use Socialbox\Classes\StandardMethods\Settings\SettingsDeleteOtp;
    use Socialbox\Classes\StandardMethods\Settings\SettingsDeletePassword;
    use Socialbox\Classes\StandardMethods\Settings\SettingsDeleteSignature;
    use Socialbox\Classes\StandardMethods\Settings\SettingsGetInformationField;
    use Socialbox\Classes\StandardMethods\Settings\SettingsGetInformationFields;
    use Socialbox\Classes\StandardMethods\Settings\SettingsGetSignature;
    use Socialbox\Classes\StandardMethods\Settings\SettingsGetSignatures;
    use Socialbox\Classes\StandardMethods\Settings\SettingsInformationFieldExists;
    use Socialbox\Classes\StandardMethods\Settings\SettingsSetOtp;
    use Socialbox\Classes\StandardMethods\Settings\SettingsSetPassword;
    use Socialbox\Classes\StandardMethods\Settings\SettingsSignatureExists;
    use Socialbox\Classes\StandardMethods\Settings\SettingsUpdateInformationField;
    use Socialbox\Classes\StandardMethods\Settings\SettingsUpdateInformationPrivacy;
    use Socialbox\Classes\StandardMethods\Settings\SettingsUpdatePassword;
    use Socialbox\Classes\StandardMethods\Verification\VerificationAuthenticate;
    use Socialbox\Classes\StandardMethods\Verification\VerificationAnswerImageCaptcha;
    use Socialbox\Classes\StandardMethods\Verification\VerificationGetImageCaptcha;
    use Socialbox\Classes\StandardMethods\Verification\VerificationOtpAuthentication;
    use Socialbox\Classes\StandardMethods\Verification\VerificationPasswordAuthentication;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\OneTimePasswordManager;
    use Socialbox\Managers\PasswordManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\Database\SessionRecord;
    use Socialbox\Objects\RpcRequest;

    enum StandardMethods : string
    {
        // AddressBook Methods
        case ADDRESS_BOOK_ADD_CONTACT = 'addressBookAddContact';
        case ADDRESS_BOOK_CONTACT_EXISTS = 'addressBookContactExists';
        case ADDRESS_BOOK_DELETE_CONTACT = 'addressBookDeleteContact';
        case ADDRESS_BOOK_GET_CONTACT = 'addressBookGetContact';
        case ADDRESS_BOOK_GET_CONTACTS = 'addressBookGetContacts';
        case ADDRESS_BOOK_TRUST_SIGNATURE = 'addressBookTrustSignature';
        case ADDRESS_BOOK_REVOKE_SIGNATURE = 'addressBookRevokeSignature';
        case ADDRESS_BOOK_UPDATE_RELATIONSHIP = 'addressBookUpdateRelationship';

        // Core Methods
        case GET_ALLOWED_METHODS = 'getAllowedMethods';
        case GET_SELF = 'getSelf';
        case GET_SESSION_STATE = 'getSessionState';
        case PING = 'ping';
        case RESOLVE_PEER = 'resolvePeer';
        case RESOLVE_SIGNATURE = 'resolveSignature';
        case VERIFY_SIGNATURE = 'verifySignature';

        // Encryption Channel Methods
        case ENCRYPTION_ACCEPT_CHANNEL = 'encryptionAcceptChannel';
        case ENCRYPTION_CHANNEL_ACKNOWLEDGE_MESSAGE = 'encryptionChannelAcknowledgeMessage';
        case ENCRYPTION_CHANNEL_EXISTS = 'encryptionChannelExists';
        case ENCRYPTION_CHANNEL_RECEIVE = 'encryptionChannelReceive';
        case ENCRYPTION_CHANNEL_REJECT_MESSAGE = 'encryptionChannelRejectMessage';
        case ENCRYPTION_CHANNEL_SEND = 'encryptionChannelSend';
        case ENCRYPTION_CLOSE_CHANNEL = 'encryptionCloseChannel';
        case ENCRYPTION_CREATE_CHANNEL = 'encryptionCreateChannel';
        case ENCRYPTION_DECLINE_CHANNEL = 'encryptionDeclineChannel';
        case ENCRYPTION_DELETE_CHANNEL = 'encryptionDeleteChannel';
        case ENCRYPTION_GET_CHANNEL = 'encryptionGetChannel';
        case ENCRYPTION_GET_CHANNEL_REQUESTS = 'encryptionGetChannelRequests';
        case ENCRYPTION_GET_CHANNELS = 'encryptionGetChannels';
        case ENCRYPTION_GET_INCOMING_CHANNELS = 'encryptionGetIncomingChannels';
        case ENCRYPTION_GET_OUTGOING_CHANNELS = 'encryptionGetOutgoingChannels';

        // ServerDocument Methods
        case ACCEPT_COMMUNITY_GUIDELINES = 'acceptCommunityGuidelines';
        case ACCEPT_PRIVACY_POLICY = 'acceptPrivacyPolicy';
        case ACCEPT_TERMS_OF_SERVICE = 'acceptTermsOfService';
        case GET_COMMUNITY_GUIDELINES = 'getCommunityGuidelines';
        case GET_PRIVACY_POLICY = 'getPrivacyPolicy';
        case GET_TERMS_OF_SERVICE = 'getTermsOfService';

        // Settings Methods
        case SETTINGS_ADD_INFORMATION_FIELD = 'settingsAddInformationField';
        case SETTINGS_ADD_SIGNATURE = 'settingsAddSigningKey';
        case SETTINGS_DELETE_INFORMATION_FIELD = 'settingsDeleteInformationField';
        case SETTINGS_DELETE_OTP = 'settingsDeleteOtp';
        case SETTINGS_DELETE_PASSWORD = 'settingsDeletePassword';
        case SETTINGS_DELETE_SIGNATURE = 'settingsDeleteSigningKey';
        case SETTINGS_GET_INFORMATION_FIELD = 'settingsGetInformationField';
        case SETTINGS_GET_INFORMATION_FIELDS = 'settingsGetInformationFields';
        case SETTINGS_GET_SIGNATURE = 'settingsGetSignature';
        case SETTINGS_GET_SIGNATURES = 'settingsGetSignatures';
        case SETTINGS_INFORMATION_FIELD_EXISTS = 'settingsInformationFieldExists';
        case SETTINGS_SET_OTP = 'settingsSetOtp';
        case SETTINGS_SET_PASSWORD = 'settingsSetPassword';
        case SETTINGS_SIGNATURE_EXISTS = 'settingsSignatureExists';
        case SETTINGS_UPDATE_INFORMATION_FIELD = 'settingsUpdateInformationField';
        case SETTINGS_UPDATE_INFORMATION_PRIVACY = 'settingsUpdateInformationPrivacy';
        case SETTINGS_UPDATE_PASSWORD = 'settingsUpdatePassword';

        // Verification Methods
        case VERIFICATION_ANSWER_IMAGE_CAPTCHA = 'verificationAnswerImageCaptcha';
        case VERIFICATION_AUTHENTICATE = 'authenticate';
        case VERIFICATION_GET_IMAGE_CAPTCHA = 'verificationGetImageCaptcha';
        case VERIFICATION_OTP_AUTHENTICATION = 'verificationOtpAuthentication';
        case VERIFICATION_PASSWORD_AUTHENTICATION = 'verificationPasswordAuthentication';
        // NOT IMPLEMENTED VERIFICATION METHODS
        case VERIFICATION_EMAIL = 'verificationEmail'; // NOT IMPLEMENTED
        case VERIFICATION_ANSWER_EMAIL = 'verificationAnswerEmail'; // NOT IMPLEMENTED
        case VERIFICATION_SMS = 'verificationSms'; // NOT IMPLEMENTED
        case VERIFICATION_ANSWER_SMS = 'verificationAnswerSms'; // NOT IMPLEMENTED
        case VERIFICATION_PHONE_CALL = 'verificationPhoneCall'; // NOT IMPLEMENTED
        case VERIFICATION_ANSWER_PHONE_CALL = 'verificationAnswerPhoneCall'; // NOT IMPLEMENTED
        case VERIFICATION_GET_TEXT_CAPTCHA = 'verificationGetTextCaptcha'; // NOT IMPLEMENTED
        case VERIFICATION_ANSWER_TEXT_CAPTCHA = 'verificationAnswerTextCaptcha'; // NOT IMPLEMENTED
        case VERIFICATION_GET_EXTERNAL_URL = 'verificationGetExternalUrl'; // NOT IMPLEMENTED
        case VERIFICATION_ANSWER_EXTERNAL_URL = 'verificationAnswerExternalUrl'; // NOT IMPLEMENTED


        // TODO: COMPLETE THE REST
        // MISC
        case GET_STATE = 'getState';

        // Messaging methods
        case MESSAGES_GET_INBOX = 'messagesGetInbox';
        case MESSAGES_GET_UNTRUSTED = 'messagesGetUntrusted';
        case MESSAGES_GET_ARCHIVED = 'messagesGetArchived';
        case MESSAGES_GET_OUTBOX = 'messagesGetOutbox';
        case MESSAGES_GET_MESSAGE = 'messagesGetMessage';
        case MESSAGES_GET_DRAFTS = 'messagesGetDrafts';
        case MESSAGES_GET_DRAFT = 'messagesGetDraft';
        case MESSAGES_TOGGLE_MESSAGE_READ = 'messagesToggleMessageRead';
        case MESSAGES_TOGGLE_MESSAGE_STAR = 'messagesToggleMessageStar';
        case MESSAGES_TOGGLE_MESSAGE_FLAG = 'messagesToggleMessageFlag';
        case MESSAGES_ARCHIVE_MESSAGE = 'messagesArchiveMessage';
        case MESSAGES_UNARCHIVE_MESSAGE = 'messagesUnarchiveMessage';
        case MESSAGES_DELETE_MESSAGE = 'messagesDeleteMessage';
        case MESSAGES_DELETE_DRAFT = 'messagesDeleteDraft';
        case MESSAGES_COMPOSE_NEW_MESSAGE = 'messagesComposeNewMessage';
        case MESSAGES_COMPOSE_REPLY_MESSAGE = 'messagesComposeReplyMessage';
        case MESSAGES_COMPOSE_FORWARD_MESSAGE = 'messagesComposeForwardMessage';
        case MESSAGES_SET_MESSAGE_RECIPIENTS = 'messagesSetMessageRecipients';
        case MESSAGES_SET_MESSAGE_CARBON_COPY_RECIPIENTS = 'messagesSetMessageCarbonCopyRecipients';
        case MESSAGES_SET_MESSAGE_BLIND_CARBON_COPY_RECIPIENTS = 'messagesSetMessageBlindCarbonCopyRecipients';
        case MESSAGES_SET_MESSAGE_ENCRYPTION_CHANNEL = 'messagesSetMessageEncryptionChannel';
        case MESSAGES_SET_MESSAGE_SUBJECT = 'messagesSetMessageSubject';
        case MESSAGES_SET_MESSAGE_BODY = 'messagesSetMessageBody';
        case MESSAGES_SEND_MESSAGE = 'messagesSendMessage';

        /**
         * Executes the appropriate operation based on the current context and requests provided.
         *
         * @param ClientRequest $request The client request object containing necessary data for execution.
         * @param RpcRequest $rpcRequest The RPC request object providing additional parameters for execution.
         * @return SerializableInterface|null The result of the operation as a serializable interface or null if no operation matches.
         * @throws StandardRpcException If an error occurs during execution
         */
        public function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            return match ($this)
            {
                // AddressBook Methods
                self::ADDRESS_BOOK_ADD_CONTACT => AddressBookAddContact::execute($request, $rpcRequest),
                self::ADDRESS_BOOK_CONTACT_EXISTS => AddressBookContactExists::execute($request, $rpcRequest),
                self::ADDRESS_BOOK_DELETE_CONTACT => AddressBookDeleteContact::execute($request, $rpcRequest),
                self::ADDRESS_BOOK_GET_CONTACT => AddressBookGetContact::execute($request, $rpcRequest),
                self::ADDRESS_BOOK_GET_CONTACTS => AddressBookGetContacts::execute($request, $rpcRequest),
                self::ADDRESS_BOOK_TRUST_SIGNATURE => AddressBookTrustSignature::execute($request, $rpcRequest),
                self::ADDRESS_BOOK_REVOKE_SIGNATURE => AddressBookRevokeSignature::execute($request, $rpcRequest),
                self::ADDRESS_BOOK_UPDATE_RELATIONSHIP => AddressBookUpdateRelationship::execute($request, $rpcRequest),

                // Core Methods
                self::GET_ALLOWED_METHODS => GetAllowedMethods::execute($request, $rpcRequest),
                self::GET_SELF => GetSelf::execute($request, $rpcRequest),
                self::GET_SESSION_STATE => GetSessionState::execute($request, $rpcRequest),
                self::PING => Ping::execute($request, $rpcRequest),
                self::RESOLVE_PEER => ResolvePeer::execute($request, $rpcRequest),
                self::RESOLVE_SIGNATURE => ResolveSignature::execute($request, $rpcRequest),
                self::VERIFY_SIGNATURE => VerifySignature::execute($request, $rpcRequest),

                // Encryption Channel Methods
                self::ENCRYPTION_ACCEPT_CHANNEL => EncryptionAcceptChannel::execute($request, $rpcRequest),
                self::ENCRYPTION_CHANNEL_ACKNOWLEDGE_MESSAGE => EncryptionChannelAcknowledgeMessage::execute($request, $rpcRequest),
                self::ENCRYPTION_CHANNEL_EXISTS => EncryptionChannelExists::execute($request, $rpcRequest),
                self::ENCRYPTION_CHANNEL_RECEIVE => EncryptionChannelReceive::execute($request, $rpcRequest),
                self::ENCRYPTION_CHANNEL_REJECT_MESSAGE => EncryptionChannelRejectMessage::execute($request, $rpcRequest),
                self::ENCRYPTION_CHANNEL_SEND => EncryptionChannelSend::execute($request, $rpcRequest),
                self::ENCRYPTION_CLOSE_CHANNEL => EncryptionCloseChannel::execute($request, $rpcRequest),
                self::ENCRYPTION_CREATE_CHANNEL => EncryptionCreateChannel::execute($request, $rpcRequest),
                self::ENCRYPTION_DECLINE_CHANNEL => EncryptionDeclineChannel::execute($request, $rpcRequest),
                self::ENCRYPTION_DELETE_CHANNEL => EncryptionDeleteChannel::execute($request, $rpcRequest),
                self::ENCRYPTION_GET_CHANNEL => EncryptionGetChannel::execute($request, $rpcRequest),
                self::ENCRYPTION_GET_CHANNEL_REQUESTS => EncryptionGetChannelRequests::execute($request, $rpcRequest),
                self::ENCRYPTION_GET_CHANNELS => EncryptionGetChannels::execute($request, $rpcRequest),
                self::ENCRYPTION_GET_INCOMING_CHANNELS => EncryptionGetIncomingChannels::execute($request, $rpcRequest),
                self::ENCRYPTION_GET_OUTGOING_CHANNELS => EncryptionGetOutgoingChannels::execute($request, $rpcRequest),

                // Server Document Methods
                self::ACCEPT_PRIVACY_POLICY => AcceptPrivacyPolicy::execute($request, $rpcRequest),
                self::ACCEPT_COMMUNITY_GUIDELINES => AcceptCommunityGuidelines::execute($request, $rpcRequest),
                self::ACCEPT_TERMS_OF_SERVICE => AcceptTermsOfService::execute($request, $rpcRequest),
                self::GET_COMMUNITY_GUIDELINES => GetCommunityGuidelines::execute($request, $rpcRequest),
                self::GET_PRIVACY_POLICY => GetPrivacyPolicy::execute($request, $rpcRequest),
                self::GET_TERMS_OF_SERVICE => GetTermsOfService::execute($request, $rpcRequest),

                // Settings Methods
                self::SETTINGS_ADD_INFORMATION_FIELD => SettingsAddInformationField::execute($request, $rpcRequest),
                self::SETTINGS_ADD_SIGNATURE => SettingsAddSignature::execute($request, $rpcRequest),
                self::SETTINGS_DELETE_INFORMATION_FIELD => SettingsDeleteInformationField::execute($request, $rpcRequest),
                self::SETTINGS_DELETE_OTP => SettingsDeleteOtp::execute($request, $rpcRequest),
                self::SETTINGS_DELETE_PASSWORD => SettingsDeletePassword::execute($request, $rpcRequest),
                self::SETTINGS_DELETE_SIGNATURE => SettingsDeleteSignature::execute($request, $rpcRequest),
                self::SETTINGS_GET_INFORMATION_FIELD => SettingsGetInformationField::execute($request, $rpcRequest),
                self::SETTINGS_GET_INFORMATION_FIELDS => SettingsGetInformationFields::execute($request, $rpcRequest),
                self::SETTINGS_GET_SIGNATURE => SettingsGetSignature::execute($request, $rpcRequest),
                self::SETTINGS_GET_SIGNATURES => SettingsGetSignatures::execute($request, $rpcRequest),
                self::SETTINGS_INFORMATION_FIELD_EXISTS => SettingsInformationFieldExists::execute($request, $rpcRequest),
                self::SETTINGS_SET_OTP => SettingsSetOtp::execute($request, $rpcRequest),
                self::SETTINGS_SET_PASSWORD => SettingsSetPassword::execute($request, $rpcRequest),
                self::SETTINGS_SIGNATURE_EXISTS => SettingsSignatureExists::execute($request, $rpcRequest),
                self::SETTINGS_UPDATE_INFORMATION_FIELD => SettingsUpdateInformationField::execute($request, $rpcRequest),
                self::SETTINGS_UPDATE_INFORMATION_PRIVACY => SettingsUpdateInformationPrivacy::execute($request, $rpcRequest),
                self::SETTINGS_UPDATE_PASSWORD => SettingsUpdatePassword::execute($request, $rpcRequest),

                // Verification Methods
                self::VERIFICATION_ANSWER_IMAGE_CAPTCHA => VerificationAnswerImageCaptcha::execute($request, $rpcRequest),
                self::VERIFICATION_AUTHENTICATE => VerificationAuthenticate::execute($request, $rpcRequest),
                self::VERIFICATION_GET_IMAGE_CAPTCHA => VerificationGetImageCaptcha::execute($request, $rpcRequest),
                self::VERIFICATION_OTP_AUTHENTICATION => VerificationOtpAuthentication::execute($request, $rpcRequest),
                self::VERIFICATION_PASSWORD_AUTHENTICATION => VerificationPasswordAuthentication::execute($request, $rpcRequest),

                // Default Unknown/Not Implemented
                default => $rpcRequest->produceError(StandardError::METHOD_NOT_ALLOWED, sprintf("The method %s is not supported by the server", $rpcRequest->getMethod()))
            };
        }

        /**
         * Checks if the access method is allowed for the given client request.
         *
         * @param ClientRequest $clientRequest The client request instance to check access against.
         * @throws DatabaseOperationException If an error occurs while checking the database for session information.
         * @throws StandardRpcException If the method is not allowed for the given client request.
         * @return bool
         */
        public function checkAccess(ClientRequest $clientRequest): bool
        {
            return in_array($this, self::getAllowedMethods($clientRequest));
        }

        /**
         * Determines the list of allowed methods for a given client request.
         *
         * @param ClientRequest $clientRequest The client request for which allowed methods are determined.
         * @return array Returns an array of allowed methods for the provided client request.
         * @throws DatabaseOperationException If an error occurs while checking the database for session information.
         * @throws StandardRpcException
         */
        public static function getAllowedMethods(ClientRequest $clientRequest): array
        {
            // These methods should always accessible
            $methods = self::getCoreMethods();
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
                $methods = array_merge($methods, self::getAuthenticatedMethods($session));
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
         * @throws DatabaseOperationException If an error occurs while checking the database for session information.
         * @throws StandardRpcException If an error occurs while checking the database for session information.
         */
        private static function getExternalMethods(ClientRequest $clientRequest): array
        {
            $methods = [];

            $session = $clientRequest->getSession();
            if(!$session->isAuthenticated() || $session->flagExists(SessionFlags::AUTHENTICATION_REQUIRED))
            {
                $methods[] = self::VERIFICATION_AUTHENTICATE;
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
        private static function getAuthenticatedMethods(?SessionRecord $session=null): array
        {
            return array_merge(
                self::getAddressBookMethods(),
                self::getServerDocumentMethods($session),
                self::getSettingsMethods(),
                self::getVerificationMethods($session)
            );
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

            $methods = self::getSettingsMethods();

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

            return $methods;
        }

        /**
         * Retrieves the list of authentication methods available for the given client request.
         *
         * @param ClientRequest $clientRequest The client request for which the authentication methods are determined.
         * @return array The list of available authentication methods as an array of constants.
         * @throws DatabaseOperationException If an error occurs while checking the database for authentication methods.
         * @throws StandardRpcException
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

        /**
         * Returns an array of methods for managing the peer's AddressBook
         *
         * @param bool $readOnly If True, only methods related to reading will be returned.
         * @return StandardMethods[] The array of AddressBook methods to return
         */
        public static function getAddressBookMethods(bool $readOnly=false): array
        {
            if($readOnly)
            {
                return [
                    self::ADDRESS_BOOK_CONTACT_EXISTS,
                    self::ADDRESS_BOOK_GET_CONTACT,
                    self::ADDRESS_BOOK_GET_CONTACTS,
                ];
            }

            return [
                self::ADDRESS_BOOK_ADD_CONTACT,
                self::ADDRESS_BOOK_CONTACT_EXISTS,
                self::ADDRESS_BOOK_DELETE_CONTACT,
                self::ADDRESS_BOOK_GET_CONTACT,
                self::ADDRESS_BOOK_GET_CONTACTS,
                self::ADDRESS_BOOK_REVOKE_SIGNATURE,
                self::ADDRESS_BOOK_TRUST_SIGNATURE,
                self::ADDRESS_BOOK_UPDATE_RELATIONSHIP
            ];
        }

        /**
         * Returns an array of methods for the core methods of the Socialbox RPC protocol
         *
         * @return StandardMethods[] An array of Core methods
         */
        public static function getCoreMethods(): array
        {
            return [
                self::GET_ALLOWED_METHODS,
                self::GET_SELF,
                self::GET_SESSION_STATE,
                self::PING,
                self::RESOLVE_PEER,
                self::RESOLVE_SIGNATURE,
                self::VERIFY_SIGNATURE
            ];
        }

        /**
         * Returns na array of ServerDocument methods made available for the peer, if $session is false then all
         * methods would be returned, otherwise the allowed methods would be returned.
         *
         * @param SessionRecord|null $session Optional. If null, all session will return otherwise only allowed methods would be returned
         * @return StandardMethods[] An array of standard methods that are related to Server documentation
         */
        public static function getServerDocumentMethods(?SessionRecord $session=null): array
        {
            if($session === null)
            {
                return [
                    self::ACCEPT_COMMUNITY_GUIDELINES,
                    self::ACCEPT_PRIVACY_POLICY,
                    self::ACCEPT_TERMS_OF_SERVICE,
                    self::GET_COMMUNITY_GUIDELINES,
                    self::GET_PRIVACY_POLICY,
                    self::GET_TERMS_OF_SERVICE
                ];
            }

            $results = [
                self::GET_COMMUNITY_GUIDELINES,
                self::GET_PRIVACY_POLICY,
                self::GET_TERMS_OF_SERVICE
            ];

            if($session->flagExists(SessionFLags::VER_COMMUNITY_GUIDELINES))
            {
                $results[] = self::ACCEPT_COMMUNITY_GUIDELINES;
            }

            if($session->flagExists(SessionFlags::VER_PRIVACY_POLICY))
            {
                $results[] = self::ACCEPT_PRIVACY_POLICY;
            }

            if($session->flagExists(SessionFlags::VER_TERMS_OF_SERVICE))
            {
                $results[] = self::ACCEPT_TERMS_OF_SERVICE;
            }

            return $results;
        }

        /**
         * Returns an array of setting methods that are accessible.
         *
         * @return StandardMethods[]
         */
        public static function getSettingsMethods(): array
        {
            return [
                self::SETTINGS_ADD_INFORMATION_FIELD,
                self::SETTINGS_ADD_SIGNATURE,
                self::SETTINGS_DELETE_INFORMATION_FIELD,
                self::SETTINGS_DELETE_OTP,
                self::SETTINGS_DELETE_PASSWORD,
                self::SETTINGS_DELETE_SIGNATURE,
                self::SETTINGS_GET_INFORMATION_FIELD,
                self::SETTINGS_GET_INFORMATION_FIELDS,
                self::SETTINGS_GET_SIGNATURE,
                self::SETTINGS_GET_SIGNATURES,
                self::SETTINGS_INFORMATION_FIELD_EXISTS,
                self::SETTINGS_SET_OTP,
                self::SETTINGS_SET_PASSWORD,
                self::SETTINGS_SIGNATURE_EXISTS,
                self::SETTINGS_UPDATE_INFORMATION_FIELD,
                self::SETTINGS_UPDATE_INFORMATION_PRIVACY,
                self::SETTINGS_UPDATE_PASSWORD
            ];
        }

        /**
         * Returns an array of verification methods that are accessible, if $session is null, all methods are returned,
         * otherwise only accessible methods are returned.
         *
         * @return StandardMethods[]
         */
        public static function getVerificationMethods(?SessionRecord $session=null): array
        {
            if($session === null)
            {
                return [
                    self::VERIFICATION_ANSWER_IMAGE_CAPTCHA,
                    self::VERIFICATION_AUTHENTICATE,
                    self::VERIFICATION_GET_IMAGE_CAPTCHA,
                    self::VERIFICATION_OTP_AUTHENTICATION,
                    self::VERIFICATION_PASSWORD_AUTHENTICATION
                ];
            }

            $results = [];

            if($session->flagExists(SessionFlags::VER_IMAGE_CAPTCHA))
            {
                $results[] = self::VERIFICATION_GET_IMAGE_CAPTCHA;
                $results[] = self::VERIFICATION_ANSWER_IMAGE_CAPTCHA;
            }

            if($session->flagExists(SessionFlags::VER_AUTHENTICATION))
            {
                $results[] = self::VERIFICATION_AUTHENTICATE;
            }

            if($session->flagExists(SessionFlags::VER_OTP))
            {
                $results[] = self::VERIFICATION_OTP_AUTHENTICATION;
            }

            if($session->flagExists(SessionFlags::VER_PASSWORD))
            {
                $results[] = self::VERIFICATION_PASSWORD_AUTHENTICATION;
            }

            return $results;
        }
    }