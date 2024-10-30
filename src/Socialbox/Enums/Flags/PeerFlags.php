<?php

namespace Socialbox\Enums\Flags;

use Socialbox\Classes\Logger;

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
    case VER_SET_DISPLAY_NAME = 'VER_SET_DISPLAY_NAME';
    case VER_EMAIL = 'VER_EMAIL';
    case VER_SMS = 'VER_SMS';
    case VER_PHONE_CALL = 'VER_PHONE_CALL';
    case VER_SOLVE_IMAGE_CAPTCHA = 'VER_SOLVE_IMAGE_CAPTCHA';

    /**
     * Converts an array of PeerFlags enums to a string representation
     *
     * @param PeerFlags[] $flags Array of PeerFlags enums
     * @return string Comma-separated string of flag values
     */
    public static function toString(array $flags): string
    {
        return implode(',', array_map(fn(PeerFlags $flag) => $flag->value, $flags));
    }

    /**
     * Converts a string representation back to an array of PeerFlags enums
     *
     * @param string $flagString Comma-separated string of flag values
     * @return PeerFlags[] Array of PeerFlags enums
     */
    public static function fromString(string $flagString): array
    {
        if (empty($flagString))
        {
            return [];
        }

        return array_map(fn(string $value) => PeerFlags::from(trim($value)), explode(',', $flagString));
    }

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
