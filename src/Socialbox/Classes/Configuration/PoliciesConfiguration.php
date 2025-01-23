<?php

    namespace Socialbox\Classes\Configuration;

    class PoliciesConfiguration
    {
        private int $maxSigningKeys;
        private int $sessionInactivityExpires;
        private int $imageCaptchaExpires;
        private int $peerSyncInterval;
        private int $getContactsLimit;

        public function __construct(array $data)
        {
            $this->maxSigningKeys = $data['max_signing_keys'];
            $this->sessionInactivityExpires = $data['session_inactivity_expires'];
            $this->imageCaptchaExpires = $data['image_captcha_expires'];
            $this->peerSyncInterval = $data['peer_sync_interval'];
            $this->getContactsLimit = $data['get_contacts_limit'];
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
    }