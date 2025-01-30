<?php

    namespace Socialbox\Exceptions\Standard;

    use Socialbox\Enums\StandardError;

    class InvalidRpcArgumentException extends StandardException
    {
        /**
         * Thrown when a required parameter is missing
         *
         * @param string $parameterName The name of the parameter that is missing
         * @param string $reason The reason why the parameter is invalid
         */
        public function __construct(string $parameterName, string $reason)
        {
            parent::__construct(sprintf('The parameter %s is invalid: %s', $parameterName, $reason), StandardError::RPC_INVALID_ARGUMENTS);
        }
    }