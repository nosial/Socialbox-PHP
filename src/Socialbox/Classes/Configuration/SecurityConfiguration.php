<?php

    namespace Socialbox\Classes\Configuration;

    class SecurityConfiguration
    {
        private bool $displayInternalExceptions;
        private int $resolvedServersTtl;
        private int $captchaTtl;
        private int $otpSecretKeyLength;
        private int $otpTimeStep;
        private int $otpDigits;
        private string $otpHashAlgorithm;
        private int $otpWindow;

        /**
         * Constructor method for initializing class properties.
         *
         * @param array $data An associative array containing values for initializing the properties.
         *
         * @return void
         */
        public function __construct(array $data)
        {
            $this->displayInternalExceptions = $data['display_internal_exceptions'];
            $this->resolvedServersTtl = $data['resolved_servers_ttl'];
            $this->captchaTtl = $data['captcha_ttl'];
            $this->otpSecretKeyLength = $data['otp_secret_key_length'];
            $this->otpTimeStep = $data['otp_time_step'];
            $this->otpDigits = $data['otp_digits'];
            $this->otpHashAlgorithm = $data['otp_hash_algorithm'];
            $this->otpWindow = $data['otp_window'];
        }

        /**
         * Determines if the display of internal errors is enabled.
         *
         * @return bool True if the display of internal errors is enabled, false otherwise.
         */
        public function isDisplayInternalExceptions(): bool
        {
            return $this->displayInternalExceptions;
        }

        /**
         * Retrieves the time-to-live (TTL) value for resolved servers.
         *
         * @return int The TTL value for resolved servers.
         */
        public function getResolvedServersTtl(): int
        {
            return $this->resolvedServersTtl;
        }

        /**
         * Retrieves the time-to-live (TTL) value for captchas.
         *
         * @return int The TTL value for captchas.
         */
        public function getCaptchaTtl(): int
        {
            return $this->captchaTtl;
        }

        /**
         * Retrieves the length of the secret key used for OTP generation.
         *
         * @return int The length of the secret key used for OTP generation.
         */
        public function getOtpSecretKeyLength(): int
        {
            return $this->otpSecretKeyLength;
        }

        /**
         * Retrieves the time step value for OTP generation.
         *
         * @return int The time step value for OTP generation.
         */
        public function getOtpTimeStep(): int
        {
            return $this->otpTimeStep;
        }

        /**
         * Retrieves the number of digits in the OTP.
         *
         * @return int The number of digits in the OTP.
         */
        public function getOtpDigits(): int
        {
            return $this->otpDigits;
        }

        /**
         * Retrieves the hash algorithm used for OTP generation.
         *
         * @return string The hash algorithm used for OTP generation.
         */
        public function getOtpHashAlgorithm(): string
        {
            return $this->otpHashAlgorithm;
        }

        /**
         * Retrieves the window value for OTP generation.
         *
         * @return int The window value for OTP generation.
         */
        public function getOtpWindow(): int
        {
            return $this->otpWindow;
        }
    }