<?php

namespace Socialbox\Exceptions;

use Exception;
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
}