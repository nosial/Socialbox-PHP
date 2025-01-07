<?php

    namespace Socialbox\Classes\Configuration;

    class AuthenticationConfiguration
    {
        private bool $enabled;
        private bool $imageCaptchaVerificationRequired;

        /**
         * Public Constructor for the AuthenticationConfiguration class
         *
         * @param array $data The array data configuration
         */
        public function __construct(array $data)
        {
            $this->enabled = (bool)$data['enabled'];
            $this->imageCaptchaVerificationRequired = (bool)$data['image_captcha_verification_required'];
        }

        /**
         * @return bool
         */
        public function isEnabled(): bool
        {
            return $this->enabled;
        }

        /**
         * @return bool
         */
        public function isImageCaptchaVerificationRequired(): bool
        {
            return $this->imageCaptchaVerificationRequired;
        }
    }