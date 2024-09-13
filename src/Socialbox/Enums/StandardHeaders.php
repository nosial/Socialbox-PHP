<?php

namespace Socialbox\Enums;

/**
 * Enumeration of standard header names used in HTTP communication.
 */
enum StandardHeaders : string
{
    case CONTENT_TYPE = 'Content-Type';
    case CLIENT_NAME = 'Client-Name';
    case CLIENT_VERSION = 'Client-Version';
    case SESSION_UUID = 'Session-UUID';
    case FROM_PEER = 'From-Peer';
    case SIGNATURE = 'Signature';

    /**
     * Determines if the current instance is required based on its type.
     *
     * @return bool Returns true if the instance is of type CONTENT_TYPE, CLIENT_VERSION, or CLIENT_NAME; otherwise, false.
     */
    public function isRequired(): bool
    {
        return match($this)
        {
            self::CONTENT_TYPE,
            self::CLIENT_VERSION,
            self::CLIENT_NAME
                => true,

            default => false,
        };
    }

    /**
     * Retrieves an array of required headers.
     *
     * @return array An array containing only the headers that are marked as required.
     */
    public static function getRequiredHeaders(): array
    {
        /** @var StandardHeaders $header */
        return array_filter(StandardHeaders::toArray(), fn($header) => $header->isRequired());
    }

    /**
     * @return array
     */
    public static function toArray(): array
    {
        $results = [];
        foreach(StandardHeaders::values() as $header)
        {
            $results[$header->getValue()] = $header;
        }

        return $results;
    }
}