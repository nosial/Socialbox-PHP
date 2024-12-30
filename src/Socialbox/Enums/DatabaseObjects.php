<?php

    namespace Socialbox\Enums;

    enum DatabaseObjects : string
    {
        case VARIABLES = 'variables.sql';
        case ENCRYPTION_RECORDS = 'encryption_records.sql';
        case RESOLVED_SERVERS = 'resolved_servers.sql';

        case REGISTERED_PEERS = 'registered_peers.sql';

        case AUTHENTICATION_PASSWORDS = 'authentication_passwords.sql';
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
                self::VARIABLES, self::ENCRYPTION_RECORDS, self::RESOLVED_SERVERS => 0,
                self::REGISTERED_PEERS => 1,
                self::AUTHENTICATION_PASSWORDS, self::CAPTCHA_IMAGES, self::SESSIONS, self::EXTERNAL_SESSIONS => 2,
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
