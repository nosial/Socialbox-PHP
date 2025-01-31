<?php

    namespace Socialbox\Exceptions\Standard;

    use Exception;
    use InvalidArgumentException;
    use Socialbox\Enums\StandardError;

    class InvalidRpcArgumentException extends StandardRpcException
    {
        /**
         * Thrown when a required parameter is missing
         *
         * @param string $parameterName The name of the parameter that is missing
         * @param string|Exception|null $reason The reason why the parameter is invalid can be a string or an exception or null
         */
        public function __construct(string $parameterName, null|string|Exception $reason=null)
        {
            if(is_null($reason))
            {
                parent::__construct(sprintf('Invalid parameter %s', $parameterName), StandardError::RPC_INVALID_ARGUMENTS);
                return;
            }

            if($reason instanceof InvalidArgumentException)
            {
                parent::__construct(sprintf('Invalid parameter %s: %s', $parameterName, $reason->getMessage()), StandardError::RPC_INVALID_ARGUMENTS, $reason);
                return;
            }

            parent::__construct(sprintf('Invalid parameter %s: %s', $parameterName, $reason), StandardError::RPC_INVALID_ARGUMENTS);
        }
    }