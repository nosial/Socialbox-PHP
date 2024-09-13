<?php

namespace Socialbox\Enums;

enum ReservedUsernames : string
{
    case HOST = 'host';
    case ANONYMOUS = 'anonymous';
    case ADMIN = 'admin';
}