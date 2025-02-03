<?php

    namespace Socialbox\Enums\Status;

    enum SignatureVerificationStatus : string
    {
        /**
         * The provided signature does not match the expected signature.
         */
        case INVALID = 'INVALID';

        /**
         * The provided signature was valid but the public key used to verify the signature was not the expected public key.
         */
        case PUBLIC_KEY_MISMATCH = 'PUBLIC_KEY_MISMATCH';

        /**
         * The provided signature was valid but the UUID used to verify the signature was not the expected UUID.
         */
        case UUID_MISMATCH = 'UUID_MISMATCH';

        /**
         * The provided signature was valid but the signing key has expired.
         */
        case EXPIRED = 'EXPIRED';

        /**
         * The provided signature was valid but unable to be verified against the peer's known public key.
         */
        case UNVERIFIED = 'UNVERIFIED';

        /**
         * The provided signature was valid and verified locally and against the peer's known public key successfully.
         */
        case VERIFIED = 'VERIFIED';
    }
