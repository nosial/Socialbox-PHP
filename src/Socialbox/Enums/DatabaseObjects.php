<?php

namespace Socialbox\Enums;

enum DatabaseObjects : string
{
    case PASSWORD_AUTHENTICATION = 'password_authentication.sql';
    case REGISTERED_PEERS = 'registered_peers.sql';
    case SESSIONS = 'sessions.sql';
    case VARIABLES = 'variables.sql';

    /**
     * Returns the priority of the database object
     *
     * @return int The priority of the database object
     */
    public function getPriority(): int
    {
        return match ($this)
        {
            self::VARIABLES => 0,
            self::REGISTERED_PEERS => 1,
            self::PASSWORD_AUTHENTICATION, self::SESSIONS => 2,
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
