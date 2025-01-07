<?php

    namespace Socialbox\Classes\StandardMethods;

    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\StandardMethods;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class GetAllowedMethods extends Method
    {

        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            $allowedMethods = [];

            try
            {
                foreach(StandardMethods::getAllowedMethods($request) as $method)
                {
                    $allowedMethods[] = $method->value;
                }
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardException('Failed to retrieve allowed methods due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse($allowedMethods);
        }
    }