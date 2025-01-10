<?php

    namespace Socialbox\Enums\Flags;

    enum SessionFlags : string
    {
        // Session states
        case REGISTRATION_REQUIRED = 'REGISTRATION_REQUIRED'; // Peer has to register
        case AUTHENTICATION_REQUIRED = 'AUTHENTICATION_REQUIRED'; // Peer has to authenticate

        // Verification, require fields
        case SET_PASSWORD = 'SET_PASSWORD'; // Peer has to set a password
        case SET_OTP = 'SET_OTP'; // Peer has to set an OTP
        case SET_DISPLAY_NAME = 'SET_DISPLAY_NAME'; // Peer has to set a display name
        case SET_DISPLAY_PICTURE = 'SET_DISPLAY_PICTURE'; // Peer has to set a display picture
        case SET_EMAIL = 'SET_EMAIL'; // Peer has to set an email
        case SET_PHONE = 'SET_PHONE'; // Peer has to set a phone number
        case SET_BIRTHDAY = 'SET_BIRTHDAY'; // Peer has to set a birthday

        // Verification, verification requirements
        case VER_PRIVACY_POLICY = 'VER_PRIVACY_POLICY'; // Peer has to accept the privacy policy
        case VER_TERMS_OF_SERVICE = 'VER_TERMS_OF_SERVICE'; // Peer has to accept the terms of service
        case VER_COMMUNITY_GUIDELINES = 'VER_COMMUNITY_GUIDELINES'; // Peer has to acknowledge the community guidelines
        case VER_EMAIL = 'VER_EMAIL'; // Peer has to verify their email
        case VER_SMS = 'VER_SMS'; // Peer has to verify their phone number
        case VER_PHONE_CALL = 'VER_PHONE_CALL'; // Peer has to verify their phone number via a phone call
        case VER_IMAGE_CAPTCHA = 'VER_IMAGE_CAPTCHA'; // Peer has to solve an image captcha
        case VER_TEXT_CAPTCHA = 'VER_TEXT_CAPTCHA'; // Peer has to solve a text captcha
        case VER_EXTERNAL_URL = 'VER_EXTERNAL_URL'; // Peer has to visit an external URL
        case VER_AUTHENTICATION = 'VER_AUTHENTICATION'; // External peer has to run authenticate() on their end

        // Login, require fields
        case VER_PASSWORD = 'VER_PASSWORD'; // Peer has to enter their password
        case VER_OTP = 'VER_OTP'; // Peer has to enter their OTP
        case VER_AUTHENTICATION_CODE = 'VER_AUTHENTICATION_CODE'; // Peer has to enter their authentication code

        // Session Flags
        case RATE_LIMITED = 'RATE_LIMITED'; // Peer is temporarily rate limited

        /**
         * Retrieves a list of registration-related flags.
         *
         * @return array Array of registration flags applicable for the process.
         */
        public static function getRegistrationFlags(): array
        {
            return [
                self::SET_PASSWORD->value,
                self::SET_OTP->value,
                self::SET_DISPLAY_NAME->value,
                self::SET_DISPLAY_PICTURE->value,
                self::SET_PHONE->value,
                self::SET_BIRTHDAY->value,
                self::SET_EMAIL->value,
                self::VER_PRIVACY_POLICY->value,
                self::VER_TERMS_OF_SERVICE->value,
                self::VER_COMMUNITY_GUIDELINES->value,
                self::VER_EMAIL->value,
                self::VER_SMS->value,
                self::VER_PHONE_CALL->value,
                self::VER_IMAGE_CAPTCHA->value
            ];
        }

        /**
         * Retrieves an array of authentication flags to be used for verifying user identity.
         *
         * @return array Returns an array containing the values of defined authentication flags.
         */
        public static function getAuthenticationFlags(): array
        {
            return [
                self::VER_IMAGE_CAPTCHA->value,
                self::VER_PASSWORD->value,
                self::VER_OTP->value,
                self::VER_AUTHENTICATION->value
            ];
        }

        /**
         * Converts an array of SessionFlags to a comma-separated string of their values.
         *
         * @param array $flags An array of SessionFlags objects to be converted.
         * @return string A comma-separated string of the values of the provided SessionFlags.
         */
        public static function toString(array $flags): string
        {
            return implode(',', array_map(fn(SessionFlags $flag) => $flag->value, $flags));
        }

        /**
         * Converts a comma-separated string of flag values into an array of SessionFlags objects.
         *
         * @param string $flagString A comma-separated string representing flag values.
         * @return array An array of SessionFlags objects created from the provided string.
         */
        public static function fromString(string $flagString): array
        {
            if (empty($flagString))
            {
                return [];
            }

            return array_map(fn(string $value) => SessionFlags::from(trim($value)), explode(',', $flagString));
        }

        /**
         * Determines if all required session flags for completion are satisfied based on the given array of flags.
         *
         * @param array $flags An array of session flags to evaluate. Accepts both enum values (strings) and enum objects.
         * @return bool True if all required flags for completion are satisfied, false otherwise.
         */
        public static function isComplete(array $flags): bool
        {
            $flags = array_map(function ($flag) {return is_string($flag) ? SessionFlags::from($flag) : $flag;}, $flags);
            $flags = array_map(fn(SessionFlags $flag) => $flag->value, $flags);

            if (in_array(SessionFlags::REGISTRATION_REQUIRED->value, $flags))
            {
                return !array_intersect(self::getRegistrationFlags(), $flags); // Check if the intersection is empty
            }

            if (in_array(SessionFlags::AUTHENTICATION_REQUIRED->value, $flags))
            {
                return !array_intersect(self::getAuthenticationFlags(), $flags); // Check if the intersection is empty

            }

            return true;
        }
    }
