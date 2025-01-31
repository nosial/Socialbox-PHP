<?php

    namespace Socialbox\Exceptions\Standard;

    use Exception;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Utilities;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\RpcException;
    use Socialbox\Objects\RpcError;
    use Socialbox\Objects\RpcRequest;
    use Throwable;

    class StandardRpcException extends Exception
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
            if(Configuration::getSecurityConfiguration()->isDisplayInternalExceptions())
            {
                return $request->produceError(StandardError::from($this->code), Utilities::throwableToString($this));
            }

            return $request->produceError(StandardError::from($this->code), $this->message);
        }

        public static function fromRpcException(RpcException $e): StandardRpcException
        {
            return new self($e->getMessage(), StandardError::tryFrom($e->getCode()) ?? StandardError::UNKNOWN, $e);
        }
    }