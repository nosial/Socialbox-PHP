<?php

    namespace Socialbox\Enums;

    enum DatabaseObjects : string
    {
        case VARIABLES = 'variables.sql';
        case RESOLVED_DNS_RECORDS = 'resolved_dns_records.sql';

        case PEERS = 'peers.sql';

        case PEER_INFORMATION = 'peer_information.sql';
        case AUTHENTICATION_PASSWORDS = 'authentication_passwords.sql';
        case AUTHENTICATION_OTP = 'authentication_otp.sql';
        case CAPTCHA_IMAGES = 'captcha_images.sql';
        case SESSIONS = 'sessions.sql';
        case EXTERNAL_SESSIONS = 'external_sessions.sql';

        /**
         * Returns the priority of the database object
         *
         * @return int The priority of the database object
         */
        public function getPriority(): int
        {
            return match ($this)
            {
                self::VARIABLES,
                self::RESOLVED_DNS_RECORDS => 0,

                self::PEERS => 1,

                self::PEER_INFORMATION,
                self::AUTHENTICATION_PASSWORDS,
                self::AUTHENTICATION_OTP,
                self::CAPTCHA_IMAGES,
                self::SESSIONS,
                self::EXTERNAL_SESSIONS => 2,
            };
        }

        /**
         * Returns an array of cases ordered by their priority.
         *
         * @return array The array of cases sorted by their priority.
         */
        public static function casesOrdered(): array
        {
            $cases = self::cases();
            usort($cases, fn($a, $b) => $a->getPriority() <=> $b->getPriority());
            return $cases;
        }
    }
