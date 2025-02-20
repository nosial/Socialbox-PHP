<?php

    namespace Socialbox\Classes\Configuration;

    use Socialbox\Enums\PrivacyState;

    class PoliciesConfiguration
    {
        private int $maxSigningKeys;
        private int $maxContactSigningKeys;
        private int $sessionInactivityExpires;
        private int $imageCaptchaExpires;
        private int $peerSyncInterval;
        private int $getContactsLimit;
        private int $getEncryptionChannelRequestsLimit;
        private int $getEncryptionChannelsLimit;
        private int $getEncryptionChannelIncomingLimit;
        private int $getEncryptionChannelOutgoingLimit;
        private PrivacyState $defaultDisplayPicturePrivacy;
        private PrivacyState $defaultFirstNamePrivacy;
        private PrivacyState $defaultMiddleNamePrivacy;
        private PrivacyState $defaultLastNamePrivacy;
        private PrivacyState $defaultEmailAddressPrivacy;
        private PrivacyState $defaultPhoneNumberPrivacy;
        private PrivacyState $defaultBirthdayPrivacy;
        private PrivacyState $defaultUrlPrivacy;

        /**
         * Constructor method for initializing the policies configuration
         *
         * @param array $data An associative array containing the following keys:
         *                    'max_signing_keys', 'session_inactivity_expires',
         *                    'image_captcha_expires', 'peer_sync_interval',
         *                    'get_contacts_limit', 'default_display_picture_privacy',
         *                    'default_first_name_privacy', 'default_middle_name_privacy',
         *                    'default_last_name_privacy', 'default_email_address_privacy',
         *                    'default_phone_number_privacy', 'default_birthday_privacy',
         *                    'default_url_privacy'.
         *
         * @return void
         */
        public function __construct(array $data)
        {
            $this->maxSigningKeys = $data['max_signing_keys'];
            $this->maxContactSigningKeys = $data['max_contact_signing_keys'];
            $this->sessionInactivityExpires = $data['session_inactivity_expires'];
            $this->imageCaptchaExpires = $data['image_captcha_expires'];
            $this->peerSyncInterval = $data['peer_sync_interval'];
            $this->getContactsLimit = $data['get_contacts_limit'];
            $this->getEncryptionChannelRequestsLimit = $data['get_encryption_channel_requests_limit'];
            $this->getEncryptionChannelsLimit = $data['get_encryption_channels_limit'];
            $this->getEncryptionChannelIncomingLimit = $data['get_encryption_channel_incoming_limit'];
            $this->getEncryptionChannelOutgoingLimit = $data['get_encryption_channel_outgoing_limit'];
            $this->defaultDisplayPicturePrivacy = PrivacyState::tryFrom($data['default_display_picture_privacy']) ?? PrivacyState::PRIVATE;
            $this->defaultFirstNamePrivacy = PrivacyState::tryFrom($data['default_first_name_privacy']) ?? PrivacyState::PRIVATE;
            $this->defaultMiddleNamePrivacy = PrivacyState::tryFrom($data['default_middle_name_privacy']) ?? PrivacyState::PRIVATE;
            $this->defaultLastNamePrivacy = PrivacyState::tryFrom($data['default_last_name_privacy']) ?? PrivacyState::PRIVATE;
            $this->defaultEmailAddressPrivacy = PrivacyState::tryFrom($data['default_email_address_privacy']) ?? PrivacyState::PRIVATE;
            $this->defaultPhoneNumberPrivacy = PrivacyState::tryFrom($data['default_phone_number_privacy']) ?? PrivacyState::PRIVATE;
            $this->defaultBirthdayPrivacy = PrivacyState::tryFrom($data['default_birthday_privacy']) ?? PrivacyState::PRIVATE;
            $this->defaultUrlPrivacy = PrivacyState::tryFrom($data['default_url_privacy']) ?? PrivacyState::PRIVATE;
        }

        /**
         * Returns the maximum amount of signing keys a peer can register with the server at once
         *
         * @return int
         */
        public function getMaxSigningKeys(): int
        {
            return $this->maxSigningKeys;
        }

        public function getMaxContactSigningKeys(): int
        {
            return $this->maxContactSigningKeys;
        }

        /**
         * Returns the maximum amount of seconds before the session is considered expired due to inactivity
         *
         * @return int
         */
        public function getSessionInactivityExpires(): int
        {
            return $this->sessionInactivityExpires;
        }

        /**
         * Returns the maximum amount of seconds before a captcha is considered expired due to the amount of time
         * that has passed since the answer was generated, if a user fails to answer the captcha during the time
         * period then the user must request for a new captcha with a new answer.
         *
         * @return int
         */
        public function getImageCaptchaExpires(): int
        {
            return $this->imageCaptchaExpires;
        }

        /**
         * Returns the maximum amount of seconds before the external peer resolve cache is considered expired
         *
         * @return int
         */
        public function getPeerSyncInterval(): int
        {
            return $this->peerSyncInterval;
        }

        /**
         * Returns the maximum amount of contacts that can be retrieved in a single request
         *
         * @return int
         */
        public function getGetContactsLimit(): int
        {
            return $this->getContactsLimit;
        }

        /**
         * Returns the maximum number of encryption channel requests that can be retrieved in a single request
         *
         * @return int
         */
        public function getEncryptionChannelRequestsLimit(): int
        {
            return $this->getEncryptionChannelRequestsLimit;
        }

        /**
         * Returns the maximum number of encryption channels that can be retrieved in a single request
         *
         * @return int
         */
        public function getEncryptionChannelsLimit(): int
        {
            return $this->getEncryptionChannelsLimit;
        }

        /**
         * Returns the maximum number of incoming encryption channels that can be retrieved in a single request
         *
         * @return int
         */
        public function getEncryptionChannelIncomingLimit(): int
        {
            return $this->getEncryptionChannelIncomingLimit;
        }

        /**
         * Returns the maximum number of outgoing encryption channels that can be retrieved in a single request
         *
         * @return int
         */
        public function getEncryptionChannelOutgoingLimit(): int
        {
            return $this->getEncryptionChannelOutgoingLimit;
        }

        /**
         * Returns the default privacy state for the display picture
         *
         * @return PrivacyState
         */
        public function getDefaultDisplayPicturePrivacy(): PrivacyState
        {
            return $this->defaultDisplayPicturePrivacy;
        }

        /**
         * Returns the default privacy state for the first name
         *
         * @return PrivacyState
         */
        public function getDefaultFirstNamePrivacy(): PrivacyState
        {
            return $this->defaultFirstNamePrivacy;
        }
        
        /**
         * Returns the default privacy state for the middle name
         *
         * @return PrivacyState
         */
        public function getDefaultMiddleNamePrivacy(): PrivacyState
        {
            return $this->defaultMiddleNamePrivacy;
        }

        /**
         * Returns the default privacy state for the last name
         *
         * @return PrivacyState
         */
        public function getDefaultLastNamePrivacy(): PrivacyState
        {
            return $this->defaultLastNamePrivacy;
        }

        /**
         * Returns the default privacy state for the email address
         *
         * @return PrivacyState
         */
        public function getDefaultEmailAddressPrivacy(): PrivacyState
        {
            return $this->defaultEmailAddressPrivacy;
        }

        /**
         * Returns the default privacy state for the phone number
         *
         * @return PrivacyState
         */
        public function getDefaultPhoneNumberPrivacy(): PrivacyState
        {
            return $this->defaultPhoneNumberPrivacy;
        }

        /**
         * Returns the default privacy state for the birthday
         *
         * @return PrivacyState
         */
        public function getDefaultBirthdayPrivacy(): PrivacyState
        {
            return $this->defaultBirthdayPrivacy;
        }

        /**
         * Returns the default privacy state for the URL
         *
         * @return PrivacyState
         */
        public function getDefaultUrlPrivacy(): PrivacyState
        {
            return $this->defaultUrlPrivacy;
        }
    }