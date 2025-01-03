<?php

    namespace Socialbox\Enums;

    enum SigningKeyState : string
    {
        case ACTIVE = 'active';
        case EXPIRED = 'expired';
        case NOT_FOUND = 'not_found';
    }
