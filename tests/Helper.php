<?php

use Random\RandomException;
use Socialbox\Exceptions\CryptographyException;
use Socialbox\Exceptions\DatabaseOperationException;
use Socialbox\Exceptions\ResolutionException;
use Socialbox\Exceptions\RpcException;
use Socialbox\SocialClient;

    class Helper
    {
        /**
         * Generates a random username based on the given domain.
         *
         * @param string $domain The domain to be appended to the generated username.
         * @param int $length The length of the random string.
         * @param string $prefix The prefix to be appended to the generated username.
         * @return string Returns a randomly generated username in the format 'user<randomString>@<domain>'.
         */
        public static function generateRandomPeer(string $domain, int $length=16, string $prefix='userTest'): string
        {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString = '';

            for ($i = 0; $i < $length; $i++)
            {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }

            return sprintf('%s%s@%s', $prefix, $randomString, $domain);
        }

        /**
         * Generates a random string.
         *
         * @param int $length The length of the random string.
         * @return string Returns a randomly generated string.
         */
        public static function generateRandomString(int $length=16): string
        {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString = '';

            for ($i = 0; $i < $length; $i++)
            {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }

            return $randomString;
        }

        /**
         * Generates a random number.
         *
         * @param int $length The length of the random number.
         * @return int Returns a randomly generated number.
         */
        public static function generateRandomNumber(int $length=16): int
        {
            $characters = '0123456789';
            $charactersLength = strlen($characters);
            $randomString = '';

            for ($i = 0; $i < $length; $i++)
            {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }

            return (int)$randomString;
        }

        /**
         * Generates a random string of bytes.
         *
         * @param int $size The size of the random bytes.
         * @return string Returns a randomly generated string of bytes.
         * @throws RuntimeException This exception is thrown if there's an issue with the random bytes generation.
         */
        public static function gennerateRandomBytes(int $size=32): string
        {
            try
            {
                return bin2hex(random_bytes($size));
            }
            catch (RandomException $e)
            {
                throw new RuntimeException('Failed to generate random bytes.', 0, $e);
            }
        }

        /**
         * Generates a random SocialClient object based on the given domain.
         *
         * @param string $domain The domain to be appended to the generated username.
         * @param int $length The length of the random string.
         * @param string $prefix The prefix to be appended to the generated username.
         * @return SocialClient Returns a randomly generated SocialClient object.
         * @throws CryptographyException This exception is thrown if there's an issue with the cryptography.
         * @throws DatabaseOperationException This exception is thrown if there's an issue with the database operation.
         * @throws ResolutionException This exception is thrown if there's an issue with the resolution.
         * @throws RpcException This exception is thrown if there's an issue with the RPC operation.
         */
        public static function generateRandomClient(string $domain, int $length=16, string $prefix='clientTest'): SocialClient
        {
            return new SocialClient(self::generateRandomPeer($domain, $length, $prefix));
        }
    }