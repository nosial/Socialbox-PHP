<?php

/**
 * This class handles the configuration settings for user registration.
 */

namespace Socialbox\Classes\Configuration;

class RegistrationConfiguration
{
    private bool $registrationEnabled;
    private ?string $privacyPolicyDocument;
    private bool $acceptPrivacyPolicy;
    private ?string $termsOfServiceDocument;
    private bool $acceptTermsOfService;
    private bool $passwordRequired;
    private bool $otpRequired;
    private bool $displayNameRequired;
    private bool $emailVerificationRequired;
    private bool $smsVerificationRequired;
    private bool $phoneCallVerificationRequired;
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
        $this->acceptPrivacyPolicy = $data['accept_privacy_policy'] ?? true;
        $this->termsOfServiceDocument = $data['terms_of_service_document'] ?? null;
        $this->acceptTermsOfService = $data['accept_terms_of_service'] ?? true;
        $this->passwordRequired = (bool)$data['password_required'];
        $this->otpRequired = (bool)$data['otp_required'];
        $this->displayNameRequired = (bool)$data['display_name_required'];
        $this->emailVerificationRequired = (bool)$data['email_verification_required'];
        $this->smsVerificationRequired = (bool)$data['sms_verification_required'];
        $this->phoneCallVerificationRequired = (bool)$data['phone_call_verification_required'];
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
     * Checks if accepting the terms of service is required.
     *
     * @return bool Returns true if the terms of service must be accepted, false otherwise.
     */
    public function isAcceptTermsOfServiceRequired(): bool
    {
        return $this->acceptTermsOfService;
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
     * Checks if email verification is required.
     *
     * @return bool Returns true if email verification is required, false otherwise.
     */
    public function isEmailVerificationRequired(): bool
    {
        return $this->emailVerificationRequired;
    }

    /**
     * Checks if SMS verification is required.
     *
     * @return bool Returns true if SMS verification is required, false otherwise.
     */
    public function isSmsVerificationRequired(): bool
    {
        return $this->smsVerificationRequired;
    }

    /**
     * Checks if phone call verification is required.
     *
     * @return bool Returns true if phone call verification is required, false otherwise.
     */
    public function isPhoneCallVerificationRequired(): bool
    {
        return $this->phoneCallVerificationRequired;
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