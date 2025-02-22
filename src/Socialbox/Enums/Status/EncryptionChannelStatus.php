<?php

    namespace Socialbox\Enums\Status;

    enum EncryptionChannelStatus : string
    {
        case AWAITING_RECEIVER = 'AWAITING_RECEIVER';
        case SERVER_REJECTED = 'SERVER_REJECTED';
        case PEER_REJECTED = 'PEER_REJECTED';
        case ERROR = 'ERROR';
        case OPENED = 'OPENED';
        case CLOSED = 'CLOSED';
    }
