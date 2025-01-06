<?php

    namespace Socialbox\Classes\Configuration;

    class PoliciesConfiguration
    {
        private int $maxSigningKeys;
        private int $sessionInactivityExpires;
        private int $imageCaptchaExpires;

        public function __construct(array $data)
        {
            $this->maxSigningKeys = $data['max_signing_keys'];
            $this->sessionInactivityExpires = $data['session_inactivity_expires'];
            $this->imageCaptchaExpires = $data['image_captcha_expires'];
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
    }