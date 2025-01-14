<?php

    namespace Socialbox\Enums\Types;

    enum ContactRelationshipType : string
    {
        case MUTUAL = 'MUTUAL';
        case TRUSTED = 'TRUSTED';
        case BLOCKED = 'BLOCKED';
    }
