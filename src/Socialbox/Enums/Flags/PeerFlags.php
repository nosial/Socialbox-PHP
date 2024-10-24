<?php

namespace Socialbox\Enums\Flags;

enum PeerFlags : string
{
    // Administrative Flags
    case ADMIN = 'ADMIN';
    case MODERATOR = 'MODERATOR';

    // General Flags
    case VERIFIED = 'VERIFIED';

    // Verification Flags
    case VER_SET_PASSWORD = 'VER_SET_PASSWORD';
    case VER_SET_OTP = 'VER_SET_OTP';
    case VER_SOLVE_IMAGE_CAPTCHA = 'VER_SOLVE_IMAGE_CAPTCHA';

    /**
     * Returns whether the flag is public. Public flags can be seen by other peers.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return match($this)
        {
            self::VER_SET_PASSWORD,
            self::VER_SET_OTP,
            self::VER_SOLVE_IMAGE_CAPTCHA => false,
            default => true,
        };
    }
}
