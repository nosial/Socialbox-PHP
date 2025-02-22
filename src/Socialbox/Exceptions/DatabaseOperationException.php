<?php

    namespace Socialbox\Exceptions;

    use Exception;
    use Throwable;

    class DatabaseOperationException extends Exception
    {
        /**
         * DatabaseOperationException constructor.
         *
         * @param string $message
         * @param Throwable|null $throwable
         */
        public function __construct(string $message, ?Throwable $throwable=null)
        {
            parent::__construct($message, 500, $throwable);
        }
    }