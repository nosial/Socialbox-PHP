<?php

    namespace Socialbox\Exceptions\Standard;

    use InvalidArgumentException;
    use Socialbox\Enums\StandardError;
    use Throwable;

    class InvalidRpcArgumentException extends StandardRpcException
    {
        /**
         * Thrown when a required parameter is missing
         *
         * @param string|null $parameterName The name of the parameter that is missing
         * @param string|Throwable|null $reason The reason why the parameter is invalid can be a string or an exception or null
         */
        public function __construct(string|null $parameterName, null|string|Throwable $reason=null)
        {
            if($parameterName === null)
            {
                if($reason instanceof InvalidArgumentException)
                {
                    parent::__construct(sprintf('Invalid parameter: %s', $reason->getMessage()), StandardError::RPC_INVALID_ARGUMENTS, $reason);
                    return;
                }

                if(is_string($reason))
                {
                    parent::__construct(sprintf('Invalid parameter: %s', $reason), StandardError::RPC_INVALID_ARGUMENTS);
                    return;
                }

                parent::__construct('Invalid parameter', StandardError::RPC_INVALID_ARGUMENTS);
                return;
            }
            
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