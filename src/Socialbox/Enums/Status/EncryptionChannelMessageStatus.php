<?php

    namespace Socialbox\Enums\Status;

    enum EncryptionChannelMessageStatus : string
    {
        case SENT = 'SENT';
        case RECEIVED = 'RECEIVED';
        case REJECTED = 'REJECTED';
    }
