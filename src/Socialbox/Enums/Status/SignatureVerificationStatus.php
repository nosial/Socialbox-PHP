<?php

    namespace Socialbox\Enums\Status;

    enum SignatureVerificationStatus : string
    {
        /**
         * The provided signature does not match the expected signature.
         */
        case INVALID = 'INVALID';

        /**
         * The provided signature was valid but the key associated with the signature has expired.
         */
        case EXPIRED = 'EXPIRED';

        /**
         * The provided signature was valid but unable to be verified against the peer's known public key.
         */
        case UNVERIFIED = 'UNVERIFIED';
    }
