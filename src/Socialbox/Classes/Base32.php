<?php

namespace Socialbox\Classes;

use InvalidArgumentException;

class Base32
{
    /**
     * Array with all 32 characters for decoding from/encoding to base32.
     */
    private const LOOKUP_TABLE = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', //  7
        'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', // 15
        'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', // 23
        'Y', 'Z', '2', '3', '4', '5', '6', '7', // 31
        '='  // padding char
    ];

    /**
     * Allowed padding lengths for base32 data.
     */
    private const ALLOWED_PADDING = [6, 4, 3, 1, 0];

    /**
     * Decodes base32 data.
     *
     * @param string $data
     * @return string
     * @throws InvalidArgumentException
     */
    public static function decode(string $data): string
    {
        if (empty($data))
        {
            throw new InvalidArgumentException('No data provided to decode');
        }

        $chars = self::LOOKUP_TABLE;
        $chars_flipped = array_flip($chars);
        $char_count = substr_count($data, $chars[32]);

        if (!in_array($char_count, self::ALLOWED_PADDING, true))
        {
            throw new InvalidArgumentException(sprintf('Invalid padding in the base32 data: %s', $data));
        }

        for ($i = 0; $i < 4; ++$i)
        {
            if ($char_count === self::ALLOWED_PADDING[$i] &&
                substr($data, -self::ALLOWED_PADDING[$i]) !== str_repeat($chars[32], self::ALLOWED_PADDING[$i]))
            {
                throw new InvalidArgumentException(sprintf('Invalid padding in the base32 data: %s', $data));
            }
        }

        // Remove padding characters
        $data = str_replace('=', (string)null, $data);
        $data = str_split($data);
        $binary_string = (string)null;

        for ($i = 0, $data_count = count($data); $i < $data_count; $i += 8)
        {
            $x = (string)null;
            for ($j = 0; $j < 8; ++$j)
            {
                $char = $data[$i + $j] ?? null;
                if ($char === null || !isset($chars_flipped[$char]))
                {
                    throw new InvalidArgumentException(sprintf('Invalid character in the base32 data: %s', $char));
                }

                $x .= str_pad(base_convert((string)$chars_flipped[$char], 10, 2), 5, '0', STR_PAD_LEFT);
            }

            $eight_bits = str_split($x, 8);

            foreach ($eight_bits as $bits)
            {
                $binary_string .= ($y = chr((int)base_convert($bits, 2, 10))) !== false ? $y : (string)null;
            }
        }

        return $binary_string;
    }

    public static function encode(string $data): string
    {
        $binaryLength = strlen($data);
        $base32String = '';

        $buffer = 0;
        $bitsLeft = 0;

        for ($i = 0; $i < $binaryLength; $i++)
        {
            $buffer = ($buffer << 8) | (ord($data[$i]) & 0xFF);
            $bitsLeft += 8;

            while ($bitsLeft >= 5)
            {
                $base32String .= self::LOOKUP_TABLE[($buffer >> ($bitsLeft - 5)) & 0x1F];
                $bitsLeft -= 5;
            }
        }

        if ($bitsLeft > 0)
        {
            $base32String .= self::LOOKUP_TABLE[($buffer << (5 - $bitsLeft)) & 0x1F];
        }

        return $base32String;
    }
}
