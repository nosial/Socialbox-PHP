<?php

    namespace Socialbox\Enums\Status;

    enum SignatureVerificationStatus : string
    {
        /**
         * Returned if the signature is invalid
         */
        case INVALID = 'INVALID';

        /**
         * Returned if one or more of the parameters are invalid resulting in a failure to verify the signature
         */
        case ERROR = 'ERROR';

        /**
         * Returned if the signing key is not found
         */
        case NOT_FOUND = 'NOT_FOUND';

        /**
         * Returned if the signature has expired
         */
        case EXPIRED = 'EXPIRED';

        /**
         * Returned if there was an error while trying to resolve the signature locally or externally
         */
        case RESOLUTION_ERROR = 'RESOLUTION_ERROR';

        /**
         * Returned if the signature has been successfully verified
         */
        case VERIFIED = 'VERIFIED';
    }
