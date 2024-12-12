<?php

    namespace Socialbox\Enums;

    /**
     * Enumeration of standard header names used in HTTP communication.
     */
    enum StandardHeaders : string
    {
        case REQUEST_TYPE = 'Request-Type';
        case IDENTIFY_AS = 'Identify-As';
        case CLIENT_NAME = 'Client-Name';
        case CLIENT_VERSION = 'Client-Version';
        case PUBLIC_KEY = 'Public-Key';

        case SESSION_UUID = 'Session-UUID';
        case SIGNATURE = 'Signature';

        /**
         * @return array
         */
        public static function toArray(): array
        {
            $results = [];
            foreach(StandardHeaders::cases() as $header)
            {
                $results[] = $header->value;
            }

            return $results;
        }
    }