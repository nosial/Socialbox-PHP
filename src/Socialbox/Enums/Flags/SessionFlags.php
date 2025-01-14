<?php

    namespace Socialbox\Enums\Flags;

    use Socialbox\Classes\Logger;

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
                self::SET_PASSWORD,
                self::SET_OTP,
                self::SET_DISPLAY_NAME,
                self::SET_DISPLAY_PICTURE,
                self::SET_PHONE,
                self::SET_BIRTHDAY,
                self::SET_EMAIL,
                self::VER_PRIVACY_POLICY,
                self::VER_TERMS_OF_SERVICE,
                self::VER_COMMUNITY_GUIDELINES,
                self::VER_EMAIL,
                self::VER_SMS,
                self::VER_PHONE_CALL,
                self::VER_IMAGE_CAPTCHA
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
                self::VER_IMAGE_CAPTCHA,
                self::VER_PASSWORD,
                self::VER_OTP,
                self::VER_AUTHENTICATION
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
            // Map provided flags to their scalar values if they are enums
            $flagValues = array_map(fn($flag) => $flag instanceof SessionFlags ? $flag->value : $flag, $flags);

            if (in_array(SessionFlags::REGISTRATION_REQUIRED, $flags, true))
            {
                Logger::getLogger()->info('Checking registration flags');
                // Compare values instead of objects
                return empty(array_intersect(self::getScalarValues(self::getRegistrationFlags()), $flagValues));
            }

            if (in_array(SessionFlags::AUTHENTICATION_REQUIRED, $flags, true))
            {
                Logger::getLogger()->info('Checking authentication flags');
                // Compare values instead of objects
                return empty(array_intersect(self::getScalarValues(self::getAuthenticationFlags()), $flagValues));
            }

            Logger::getLogger()->info('Neither registration nor authentication flags found');
            return true;
        }

        /**
         * Helper method: Converts an array of SessionFlags enums to their scalar values (strings)
         *
         * @param array $flagEnums Array of SessionFlags objects
         * @return array Array of scalar values corresponding to the flags
         */
        private static function getScalarValues(array $flagEnums): array
        {
            return array_map(fn(SessionFlags $flag) => $flag->value, $flagEnums);
        }
    }
