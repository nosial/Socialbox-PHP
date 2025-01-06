<?php

    namespace Socialbox\Classes;

    class Validator
    {
        private const string PEER_ADDRESS_PATTERN = "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/";
        private const string USERNAME_PATTERN = "/^[a-zA-Z0-9_]+$/";

        /**
         * Validates a peer address
         *
         * @param string $address The address to validate.
         * @return bool True if the address is valid, false otherwise.
         */
        public static function validatePeerAddress(string $address): bool
        {
            return preg_match(self::PEER_ADDRESS_PATTERN, $address) === 1;
        }

        /**
         * Checks if the provided email address is in a valid email format.
         *
         * @param string $emailAddress The email address to be validated.
         * @return bool Returns true if the email address is valid, otherwise false.
         */
        public static function validateEmailAddress(string $emailAddress): bool
        {
            return filter_var($emailAddress, FILTER_VALIDATE_EMAIL) !== false;
        }

        /**
         * Validates a username
         *
         * @param string $username The username to validate.
         * @return bool True if the username is valid, false otherwise.
         */
        public static function validateUsername(string $username): bool
        {
            if(strlen($username) < 3 || strlen($username) > 255)
            {
                return false;
            }

            return preg_match(self::USERNAME_PATTERN, $username) === 1;
        }

        /**
         * Validates whether a given phone number conforms to the required format.
         *
         * @param string $phoneNumber The phone number to validate. Must start with a "+" followed by 1 to 15 digits.
         * @return bool Returns true if the phone number is valid according to the format, otherwise false.
         */
        public static function validatePhoneNumber(string $phoneNumber): bool
        {
            return preg_match("/^\+[0-9]{1,15}$/", $phoneNumber) === 1;
        }
    }