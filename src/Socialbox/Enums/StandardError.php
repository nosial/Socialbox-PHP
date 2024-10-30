<?php

namespace Socialbox\Enums;

enum StandardError : int
{
    // Fallback Codes
    case UNKNOWN = -1;

    // RPC Errors
    case RPC_METHOD_NOT_FOUND = -1000;
    case RPC_INVALID_ARGUMENTS = -1001;

    // Server Errors
    case INTERNAL_SERVER_ERROR = -2000;
    case SERVER_UNAVAILABLE = -2001;

    // Authentication/Cryptography Errors
    case INVALID_PUBLIC_KEY = -3000;
    case UNSUPPORTED_AUTHENTICATION_TYPE = -3001;
    case ALREADY_AUTHENTICATED = -3002;
    case AUTHENTICATION_REQUIRED = -3003;
    case SESSION_NOT_FOUND = -3004;
    case SESSION_REQUIRED = -3005;
    case REGISTRATION_DISABLED = -3006;

    // General Error Messages
    case PEER_NOT_FOUND = -4000;
    case INVALID_USERNAME = -4001;
    case USERNAME_ALREADY_EXISTS = -4002;

    /**
     * Returns the default generic message for the error
     *
     * @return string
     */
    public function getMessage(): string
    {
        return match ($this)
        {
            self::UNKNOWN => 'Unknown Error',

            self::RPC_METHOD_NOT_FOUND => 'The request method was not found',
            self::RPC_INVALID_ARGUMENTS => 'The request method contains one or more invalid arguments',

            self::INTERNAL_SERVER_ERROR => 'Internal server error',
            self::SERVER_UNAVAILABLE => 'Server temporarily unavailable',

            self::INVALID_PUBLIC_KEY => 'The given public key is not valid',
            self::UNSUPPORTED_AUTHENTICATION_TYPE => 'The requested authentication type is not supported by the server',
            self::ALREADY_AUTHENTICATED => 'The action cannot be preformed while authenticated',
            self::AUTHENTICATION_REQUIRED => 'Authentication is required to preform this action',
            self::SESSION_NOT_FOUND => 'The requested session UUID was not found',
            self::SESSION_REQUIRED => 'A session is required to preform this action',

            self::PEER_NOT_FOUND => 'The requested peer was not found',
            self::INVALID_USERNAME => 'The given username is invalid, it must be Alphanumeric with a minimum of 3 character but no greater than 255 characters',
            self::USERNAME_ALREADY_EXISTS => 'The given username already exists on the network'
        };

    }
}