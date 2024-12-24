<?php

namespace Socialbox\Enums\Flags;

enum PeerFlags : string
{
    // Administrative Flags
    case ADMIN = 'ADMIN';
    case MODERATOR = 'MODERATOR';

    // General Flags
    case VERIFIED = 'VERIFIED';

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
}
