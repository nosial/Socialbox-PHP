<?php

namespace Socialbox\Exceptions;

use Exception;
use Socialbox\Objects\RpcError;
use Throwable;

class RpcException extends Exception
{
    /**
     * Throws when there is an RPC exception that couldn't be handled
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message="", int $code=0, ?Throwable $previous=null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Creates an RpcException instance from an RpcError.
     *
     * @param RpcError $error The RPC error object containing details of the error.
     * @param Throwable|null $e The previous throwable used for exception chaining.
     * @return RpcException The constructed RpcException instance.
     */
    public static function fromRpcError(RpcError $error, ?Throwable $e=null): RpcException
    {
        return new RpcException($error->getError(), $error->getCode()->value, $e);
    }
}