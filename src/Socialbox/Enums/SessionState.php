<?php

namespace Socialbox\Enums;

enum SessionState : string
{
    /**
     * The session is currently active and usable
     */
    case ACTIVE = 'ACTIVE';

    /**
     * The session has expired and is no longer usable
     */
    case EXPIRED = 'EXPIRED';

    /**
     * The session was closed either by the client or the server and is no longer usable
     */
    case CLOSED = 'CLOSED';
}