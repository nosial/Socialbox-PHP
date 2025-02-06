<?php

    namespace Socialbox\Enums\Status;

    enum EncryptionChannelState : string
    {
        case AWAITING_RECEIVER = 'AWAITING_RECEIVER';
        case ERROR = 'ERROR';
        case DECLINED = 'DECLINED';
        case OPENED = 'OPENED';
        case CLOSED = 'CLOSED';
    }
