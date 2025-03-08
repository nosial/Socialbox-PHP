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

        /**
         * Validates whether the given date is a valid gregorian calendar date.
         *
         * @param int $month The month component of the date (1 through 12).
         * @param int $day The day component of the date.
         * @param int $year The year component of the date.
         * @return bool Returns true if the provided date is valid, otherwise false.
         */
        public static function validateDate(int $month, int $day, int $year): bool
        {
            return checkdate($month, $day, $year);
        }

        /**
         * Validates whether the given UUID is a valid UUID.
         *
         * @param string $uuid The UUID to validate.
         * @return bool Returns true if the provided UUID is valid, otherwise false.
         */
        public static function validateUuid(string $uuid): bool
        {
            return preg_match("/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/", $uuid) === 1;
        }

        /**
         * Checks if a given Unix timestamp falls within a specified range of the current time.
         *
         * @param int $timestamp The Unix timestamp to check.
         * @param int $range The range in seconds within which the timestamp should fall.
         * @return bool True if the timestamp is within the range, false otherwise.
         */
        public static function isTimestampInRange(int $timestamp, int $range): bool
        {
            $currentTime = time();
            return ($timestamp >= ($currentTime - $range)) && ($timestamp <= ($currentTime + $range));
        }
    }