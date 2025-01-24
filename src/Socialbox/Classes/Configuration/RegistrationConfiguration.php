<?php

    namespace Socialbox\Classes\Configuration;

    class RegistrationConfiguration
    {
        private bool $registrationEnabled;
        private ?string $privacyPolicyDocument;
        private int $privacyPolicyDate;
        private bool $acceptPrivacyPolicy;
        private ?string $termsOfServiceDocument;
        private int $termsOfServiceDate;
        private bool $acceptTermsOfService;
        private ?string $communityGuidelinesDocument;
        private int $communityGuidelinesDate;
        private bool $acceptCommunityGuidelines;
        private bool $passwordRequired;
        private bool $otpRequired;
        private bool $displayNameRequired;
        private bool $firstNameRequired;
        private bool $middleNameRequired;
        private bool $lastNameRequired;
        private bool $displayPictureRequired;
        private bool $emailAddressRequired;
        private bool $phoneNumberRequired;
        private bool $birthdayRequired;
        private bool $urlRequired;
        private bool $imageCaptchaVerificationRequired;

        /**
         * Constructor method for initializing verification requirements.
         *
         * @param array $data An associative array containing the following keys:
         *                    'registration_enabled', 'password_required',
         *                    'otp_required', 'display_name_required',
         *                    'email_verification_required', 'sms_verification_required',
         *                    'phone_call_verification_required', 'image_captcha_verification_required'.
         *
         * @return void
         */
        public function __construct(array $data)
        {
            $this->registrationEnabled = (bool)$data['enabled'];
            $this->privacyPolicyDocument = $data['privacy_policy_document'] ?? null;
            $this->privacyPolicyDate = $data['privacy_policy_date'] ?? 0;
            $this->acceptPrivacyPolicy = $data['accept_privacy_policy'] ?? true;
            $this->termsOfServiceDocument = $data['terms_of_service_document'] ?? null;
            $this->termsOfServiceDate = $data['terms_of_service_date'] ?? 0;
            $this->acceptTermsOfService = $data['accept_terms_of_service'] ?? true;
            $this->communityGuidelinesDocument = $data['community_guidelines_document'] ?? null;
            $this->communityGuidelinesDate = $data['community_guidelines_date'] ?? 0;
            $this->acceptCommunityGuidelines = $data['accept_community_guidelines'] ?? true;
            $this->passwordRequired = (bool)$data['password_required'];
            $this->otpRequired = (bool)$data['otp_required'];
            $this->displayNameRequired = (bool)$data['display_name_required'];
            $this->firstNameRequired = (bool)$data['first_name_required'];
            $this->middleNameRequired = (bool)$data['middle_name_required'];
            $this->lastNameRequired = (bool)$data['last_name_required'];
            $this->displayPictureRequired = (bool)$data['display_picture_required'];
            $this->emailAddressRequired = (bool)$data['email_address_required'];
            $this->phoneNumberRequired = (bool)$data['phone_number_required'];
            $this->birthdayRequired = (bool)$data['birthday_required'];
            $this->urlRequired = (bool)$data['url_required'];
            $this->imageCaptchaVerificationRequired = (bool)$data['image_captcha_verification_required'];
        }

        /**
         * Checks if the registration is enabled.
         *
         * @return bool True if registration is enabled, false otherwise.
         */
        public function isRegistrationEnabled(): bool
        {
            return $this->registrationEnabled;
        }

        /**
         * Retrieves the privacy policy document.
         *
         * @return ?string Returns the privacy policy document or null if not set.
         */
        public function getPrivacyPolicyDocument(): ?string
        {
            return $this->privacyPolicyDocument;
        }

        /**
         * Retrieves the date of the privacy policy.
         *
         * @return int Returns the date of the privacy policy.
         */
        public function getPrivacyPolicyDate(): int
        {
            return $this->privacyPolicyDate;
        }

        /**
         * Checks if accepting the privacy policy is required.
         *
         * @return bool Returns true if the privacy policy must be accepted, false otherwise.
         */
        public function isAcceptPrivacyPolicyRequired(): bool
        {
            return $this->acceptPrivacyPolicy;
        }

        /**
         * Retrieves the terms of service document.
         *
         * @return ?string Returns the terms of service document or null if not set.
         */
        public function getTermsOfServiceDocument(): ?string
        {
            return $this->termsOfServiceDocument;
        }

        /**
         * Retrieves the date of the terms of service.
         *
         * @return int Returns the date of the terms of service.
         */
        public function getTermsOfServiceDate(): int
        {
            return $this->termsOfServiceDate;
        }

        /**
         * Checks if accepting the terms of service is required.
         *
         * @return bool Returns true if the terms of service must be accepted, false otherwise.
         */
        public function isAcceptTermsOfServiceRequired(): bool
        {
            return $this->acceptTermsOfService;
        }

        /**
         * Retrieves the community guidelines document.
         *
         * @return ?string Returns the community guidelines document or null if not set.
         */
        public function getCommunityGuidelinesDocument(): ?string
        {
            return $this->communityGuidelinesDocument;
        }

        /**
         * Retrieves the date of the community guidelines.
         *
         * @return int Returns the date of the community guidelines.
         */
        public function getCommunityGuidelinesDate(): int
        {
            return $this->communityGuidelinesDate;
        }

        /**
         * Checks if accepting the community guidelines is required.
         *
         * @return bool Returns true if the community guidelines must be accepted, false otherwise.
         */
        public function isAcceptCommunityGuidelinesRequired(): bool
        {
            return $this->acceptCommunityGuidelines;
        }

        /**
         * Determines if a password is required.
         *
         * @return bool True if a password is required, false otherwise.
         */
        public function isPasswordRequired(): bool
        {
            return $this->passwordRequired;
        }

        /**
         * Determines if OTP (One-Time Password) is required.
         *
         * @return bool True if OTP is required, false otherwise.
         */
        public function isOtpRequired(): bool
        {
            return $this->otpRequired;
        }

        /**
         * Checks if a display name is required.
         *
         * @return bool Returns true if a display name is required, false otherwise.
         */
        public function isDisplayNameRequired(): bool
        {
            return $this->displayNameRequired;
        }

        /**
         * Checks if a first name is required.
         *
         * @return bool Returns true if a first name is required, false otherwise.
         */
        public function isFirstNameRequired(): bool
        {
            return $this->firstNameRequired;
        }

        /**
         * Checks if a middle name is required.
         *
         * @return bool Returns true if a middle name is required, false otherwise.
         */
        public function isMiddleNameRequired(): bool
        {
            return $this->middleNameRequired;
        }

        /**
         * Checks if a last name is required.
         *
         * @return bool Returns true if a last name is required, false otherwise.
         */
        public function isLastNameRequired(): bool
        {
            return $this->lastNameRequired;
        }

        /**
         * Checks if a display picture is required.
         *
         * @return bool Returns true if a display picture is required, false otherwise.
         */
        public function isDisplayPictureRequired(): bool
        {
            return $this->displayPictureRequired;
        }

        /**
         * Determines whether an email address is required.
         *
         * @return bool Returns true if an email address is required, false otherwise.
         */
        public function isEmailAddressRequired(): bool
        {
            return $this->emailAddressRequired;
        }

        /**
         * Determines if a phone number is required.
         *
         * @return bool Returns true if a phone number is required, false otherwise.
         */
        public function isPhoneNumberRequired(): bool
        {
            return $this->phoneNumberRequired;
        }

        /**
         * Determines if a birthday is required.
         *
         * @return bool Returns true if a birthday is required, otherwise false.
         */
        public function isBirthdayRequired(): bool
        {
            return $this->birthdayRequired;
        }

        /**
         * Determines if a URL is required.
         *
         * @return bool Returns true if a URL is required, false otherwise.
         */
        public function isUrlRequired(): bool
        {
            return $this->urlRequired;
        }

        /**
         * Determines if image CAPTCHA verification is required.
         *
         * @return bool Returns true if image CAPTCHA verification is required, false otherwise.
         */
        public function isImageCaptchaVerificationRequired(): bool
        {
            return $this->imageCaptchaVerificationRequired;
        }
    }