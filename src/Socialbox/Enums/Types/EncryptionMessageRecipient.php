<?php

    namespace Socialbox\Enums\Types;

    enum EncryptionMessageRecipient : string
    {
        case SENDER = 'SENDER';
        case RECEIVER = 'RECEIVER';

        /**
         * Reverses the role of the recipient
         *
         * @return EncryptionMessageRecipient
         */
        public function reverse(): EncryptionMessageRecipient
        {
            return match($this)
            {
                self::SENDER => self::RECEIVER,
                self::RECEIVER => self::SENDER
            };
        }
    }
