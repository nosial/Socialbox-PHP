<?php

    namespace Socialbox\Exceptions\Standard;

    use Socialbox\Enums\StandardError;

    class MissingArgumentException extends StandardException
    {
        /**
         * Thrown when a required parameter is missing
         *
         * @param string $parameterName
         */
        public function __construct(string $parameterName)
        {
            parent::__construct(sprintf('Missing required parameter: %s', $parameterName), StandardError::RPC_INVALID_ARGUMENTS);
        }
    }