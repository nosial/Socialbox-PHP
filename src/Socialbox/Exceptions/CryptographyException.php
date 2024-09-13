<?php

namespace Socialbox\Exceptions;

use Exception;
use Throwable;

class CryptographyException extends Exception
{
    /**
     * Thrown when an error occurs during cryptography operations.
     *
     * @param string $message The error message
     * @param int $code The error code
     * @param Throwable|null $previous Optional. The previous exception used for the exception chaining
     */
    public function __construct(string $message, int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}