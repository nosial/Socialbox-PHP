<?php

    namespace Socialbox\Enums;

    enum StandardError : int
    {
        // Fallback Codes
        case UNKNOWN = -1;

        // Generic Errors
        case INTERNAL_SERVER_ERROR = -100;
        case SERVER_UNAVAILABLE = -101;
        case BAD_REQUEST = -102;
        case FORBIDDEN = -103;
        case UNAUTHORIZED = -104;
        case NOT_FOUND = -105;
        case RESOLUTION_FAILED = -106;
        case CONFLICT = -107;
        case CRYPTOGRAPHIC_ERROR = -108;

        // RPC Errors
        case RPC_METHOD_NOT_FOUND = -1000;
        case RPC_INVALID_ARGUMENTS = -1001;
        CASE RPC_BAD_REQUEST = -1002;

        // Client Errors
        case METHOD_NOT_ALLOWED = -3001;

        // Authentication/Cryptography Errors
        case INVALID_PUBLIC_KEY = -4000; // *
        case SESSION_NOT_FOUND = -5002; // *
        case ALREADY_AUTHENTICATED = -6005;
        case UNSUPPORTED_AUTHENTICATION_TYPE = -6006;
        case AUTHENTICATION_REQUIRED = -6007;
        case REGISTRATION_DISABLED = -6008;
        case CAPTCHA_NOT_AVAILABLE = -6009;
        case INCORRECT_CAPTCHA_ANSWER = -6010;
        case CAPTCHA_EXPIRED = -6011;

        // General Error Messages
        case PEER_NOT_FOUND = -7000;
        case INVALID_USERNAME = -7001;
        case USERNAME_ALREADY_EXISTS = -7002;
        case NOT_REGISTERED = -7003;

        /**
         * Returns the default generic message for the error
         *
         * @return string
         */
        public function getMessage(): string
        {
            return match ($this)
            {
                default => 'Unknown Error',

                self::RPC_METHOD_NOT_FOUND => 'The request method was not found',
                self::RPC_INVALID_ARGUMENTS => 'The request method contains one or more invalid arguments',

                self::INTERNAL_SERVER_ERROR => 'Internal server error',
                self::SERVER_UNAVAILABLE => 'Server temporarily unavailable',

                self::INVALID_PUBLIC_KEY => 'The given public key is not valid',
                self::UNSUPPORTED_AUTHENTICATION_TYPE => 'The requested authentication type is not supported by the server',
                self::ALREADY_AUTHENTICATED => 'The action cannot be preformed while authenticated',
                self::AUTHENTICATION_REQUIRED => 'Authentication is required to preform this action',
                self::SESSION_NOT_FOUND => 'The requested session UUID was not found',
                self::REGISTRATION_DISABLED => 'Registration is disabled on the server',
                self::CAPTCHA_NOT_AVAILABLE => 'Captcha is not available',
                self::INCORRECT_CAPTCHA_ANSWER => 'The Captcha answer is incorrect',
                self::CAPTCHA_EXPIRED => 'The captcha has expired and a new captcha needs to be requested',

                self::PEER_NOT_FOUND => 'The requested peer was not found',
                self::INVALID_USERNAME => 'The given username is invalid, it must be Alphanumeric with a minimum of 3 character but no greater than 255 characters',
                self::USERNAME_ALREADY_EXISTS => 'The given username already exists on the network',
                self::NOT_REGISTERED => 'The given username is not registered on the server',
                self::METHOD_NOT_ALLOWED => 'The requested method is not allowed',
            };

        }
    }