<?php

namespace Socialbox\Enums;

enum StandardResponseCodes : int
{
    /**
     * The RPC Request was successful
     */
    case OK = 200;

    /**
     * The RPC Request was successful but no response was returned
     */
    case EMPTY = 204;

    /**
     * Bad RPC Request, fatal issue with how the client is producing the requests.
     */
    case BAD_REQUEST = 400;

    /**
     * Unexpected Internal Server error, general catch-all for anything RPC out outside related.
     * Anything internal via an RPC request should return the RpcError with the error code -2000
     */
    case INTERNAL_SERVER_ERROR = 500;
}