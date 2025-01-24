<?php

    namespace Socialbox\Enums;

    enum PrivacyState : string
    {
        case PUBLIC = 'PUBLIC';
        case PRIVATE = 'PRIVATE';
        case CONTACTS = 'CONTACTS';
        case TRUSTED = 'TRUSTED';
    }
