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

}