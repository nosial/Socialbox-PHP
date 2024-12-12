<?php

    namespace Socialbox\Exceptions;

    use Exception;
    use Throwable;

    class RequestException extends Exception
    {
        /**
         * Initializes a new instance of the exception class with a custom message, code, and a previous throwable.
         *
         * @param string $message The exception message. Defaults to an empty string.
         * @param int $code The exception code. Defaults to 0.
         * @param Throwable|null $previous The previous throwable used for exception chaining. Defaults to null.
         */
        public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
        {
            parent::__construct($message, $code, $previous);
        }
    }