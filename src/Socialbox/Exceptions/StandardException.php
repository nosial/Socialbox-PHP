<?php

    namespace Socialbox\Exceptions;

    use Exception;
    use Socialbox\Enums\StandardError;
    use Socialbox\Objects\RpcError;
    use Socialbox\Objects\RpcRequest;
    use Throwable;

    class StandardException extends Exception
    {
        /**
         * Thrown as a standard error, with a message and a code
         *
         * @param string $message
         * @param StandardError $code
         * @param Throwable|null $previous
         */
        public function __construct(string $message, StandardError $code, ?Throwable $previous=null)
        {
            parent::__construct($message, $code->value, $previous);
        }

        public function getStandardError(): StandardError
        {
            return StandardError::from($this->code);
        }

        public function produceError(RpcRequest $request): ?RpcError
        {
            return $request->produceError(StandardError::from($this->code), $this->message);
        }
    }