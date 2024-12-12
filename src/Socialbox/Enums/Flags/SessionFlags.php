<?php

    namespace Socialbox\Enums\Flags;

    enum SessionFlags : string
    {
        // Verification, require fields
        case SET_PASSWORD = 'SET_PASSWORD'; // Peer has to set a password
        case SET_OTP = 'SET_OTP'; // Peer has to set an OTP
        case SET_DISPLAY_NAME = 'SET_DISPLAY_NAME'; // Peer has to set a display name

        // Verification, verification requirements
        case VER_EMAIL = 'VER_EMAIL'; // Peer has to verify their email
        case VER_SMS = 'VER_SMS'; // Peer has to verify their phone number
        case VER_PHONE_CALL = 'VER_PHONE_CALL'; // Peer has to verify their phone number via a phone call
        case VER_IMAGE_CAPTCHA = 'VER_IMAGE_CAPTCHA'; // Peer has to solve an image captcha

        // Login, require fields
        case VER_PASSWORD = 'VER_PASSWORD'; // Peer has to enter their password
        case VER_OTP = 'VER_OTP'; // Peer has to enter their OTP

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
    }
