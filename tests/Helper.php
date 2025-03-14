<?php

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
    }