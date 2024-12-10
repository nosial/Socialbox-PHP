<?php

    namespace Socialbox\Enums\Flags;

    enum SessionFlags : string
    {
        // Verification, require fields
        case VER_SET_PASSWORD = 'VER_SET_PASSWORD'; // Peer has to set a password
        case VER_SET_OTP = 'VER_SET_OTP'; // Peer has to set an OTP
        case VER_SET_DISPLAY_NAME = 'VER_SET_DISPLAY_NAME'; // Peer has to set a display name

        // Verification, verification requirements
        case VER_EMAIL = 'VER_EMAIL'; // Peer has to verify their email
        case VER_SMS = 'VER_SMS'; // Peer has to verify their phone number
        case VER_PHONE_CALL = 'VER_PHONE_CALL'; // Peer has to verify their phone number via a phone call
        case VER_IMAGE_CAPTCHA = 'VER_IMAGE_CAPTCHA'; // Peer has to solve an image captcha

        // Login, require fields
        case VER_PASSWORD = 'VER_PASSWORD'; // Peer has to enter their password
        case VER_OTP = 'VER_OTP'; // Peer has to enter their OTP
    }
