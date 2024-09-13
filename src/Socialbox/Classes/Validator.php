<?php

namespace Socialbox\Classes;

class Validator
{
    private const PEER_ADDRESS_PATTERN = "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/";

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
}