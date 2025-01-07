<?php

    namespace Socialbox\Classes;

    use Random\RandomException;
    use Socialbox\Exceptions\CryptographyException;

    class OtpCryptography
    {
        private const string URI_FORMAT = 'otpauth://totp/%s?secret=%s%s&algorithm=%s&digits=%d&period=%d';

        /**
         * Generates a random secret key of the specified length.
         *
         * @param int $length The length of the secret key in bytes. Default is 32.
         * @return string Returns the generated secret key as a hexadecimal string.
         * @throws CryptographyException If the length is less than or equal to 0.
         * @throws RandomException If an error occurs while generating random bytes.
         */
        public static function generateSecretKey(int $length=32): string
        {
            if($length <= 0)
            {
                throw new CryptographyException("Invalid secret key length: must be greater than 0.");
            }

            return bin2hex(random_bytes($length));
        }

        /**
         * Generates a one-time password (OTP) based on the provided parameters.
         *
         * @param string $secretKey The secret key used to generate the OTP.
         * @param int $timeStep The time step in seconds used for OTP generation. Default is 30 seconds.
         * @param int $digits The number of digits in the OTP. Default is 6.
         * @param int|null $counter Optional counter value. If not provided, it is calculated based on the current time and time step.
         * @param string $hashAlgorithm The hash algorithm used for OTP generation. Default is 'sha512'.
         * @return string Returns the generated OTP as a string with the specified number of digits.
         * @throws CryptographyException If the generated hash length is less than 20 bytes.
         */
        public static function generateOTP(string $secretKey, int $timeStep=30, int $digits=6, int $counter=null, string $hashAlgorithm='sha512'): string
        {
            if ($counter === null)
            {
                $counter = floor(time() / $timeStep);
            }

            $hash = self::hashHmac($hashAlgorithm, pack('J', $counter), $secretKey);

            if (strlen($hash) < 20)
            {
                throw new CryptographyException("Invalid hash length: must be at least 20 bytes.");
            }

            // Validate the $secretKey
            if (!ctype_xdigit($secretKey))
            {
                throw new CryptographyException("Invalid secret key: must be a hexadecimal string.");
            }

            $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
            $binary = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;
            $otp = $binary % (10 ** $digits);

            return str_pad((string)$otp, $digits, '0', STR_PAD_LEFT);
        }

        /**
         * Verifies a one-time password (OTP) based on the provided parameters.
         *
         * @param string $secretKey The secret key used to generate the OTP.
         * @param string $otp The one-time password to verify.
         * @param int $timeStep The time step in seconds used for OTP generation. Default is 30 seconds.
         * @param int $window The allowed window of time steps for verification. Default is 1.
         * @param int $digits The number of digits in the OTP. Default is 6.
         * @param string $hashAlgorithm The hash algorithm used for OTP generation. Default is 'sha512'.
         * @return bool Returns true if the OTP is valid within the provided parameters, otherwise false.
         * @throws CryptographyException If the generated hash length is less than 20 bytes.
         */
        public static function verifyOTP(string $secretKey, string $otp, int $timeStep=30, int $window=1, int $digits=6, string $hashAlgorithm='sha512'): bool
        {
            $currentTime = time();
            $counter = floor($currentTime / $timeStep);

            for ($i = -$window; $i <= $window; $i++)
            {
                $testCounter = $counter + $i;
                $expectedOtp = self::generateOTP($secretKey, $timeStep, $digits, $testCounter, $hashAlgorithm);

                if (hash_equals($expectedOtp, $otp))
                {
                    return true;
                }
            }
            return false;
        }

        /**
         * Generates a key URI for use in configuring an authenticator application.
         *
         * @param string $label A unique label to identify the account (e.g., user or service name).
         * @param string $secretKey The secret key used for generating the OTP.
         * @param string|null $issuer The name of the organization or service issuing the key. Default is null.
         * @param int $timeStep The time step in seconds used for OTP generation. Default is 30 seconds.
         * @param int $digits The number of digits in the generated OTP. Default is 6.
         * @param string $hashAlgorithm The hash algorithm used for OTP generation. Default is 'sha512'.
         * @return string Returns the URI string formatted
         */
        public static function generateKeyUri(string $label, string $secretKey, ?string $issuer = null, int $timeStep=30, int $digits=6, string $hashAlgorithm='sha512'): string
        {
            $issuerPart = $issuer ? "&issuer=" . rawurlencode($issuer) : '';
            return sprintf(self::URI_FORMAT, rawurlencode($label), $secretKey, $issuerPart, strtoupper($hashAlgorithm), $digits, $timeStep);
        }

        /**
         * Computes a hash-based message authentication code (HMAC) using the specified algorithm.
         *
         * @param string $algorithm The hashing algorithm to be used (e.g., 'sha1', 'sha256', 'sha384', 'sha512').
         * @param string $data The data to be hashed.
         * @param string $key The secret key used for the HMAC generation.
         * @return string The generated HMAC as a raw binary string.
         * @throws CryptographyException If the algorithm is not supported.
         */
        private static function hashHmac(string $algorithm, string $data, string $key): string
        {
            return match($algorithm)
            {
                'sha1', 'sha256', 'sha512' => hash_hmac($algorithm, $data, $key, true),
                default => throw new CryptographyException('Algorithm not supported')
            };
        }
    }