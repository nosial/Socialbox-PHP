<?php

    namespace Socialbox;

    use ConfigLib\Configuration;
    use Exception;
    use Socialbox\Classes\RpcHandler;
    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\StandardMethods;
    use Socialbox\Exceptions\RpcException;
    use Socialbox\Exceptions\StandardException;

    class Socialbox
    {
        public static function getConfiguration(): array
        {

        }

        public static function handleRpc(): void
        {
            try
            {
                $clientRequest = RpcHandler::getClientRequest();
            }
            catch(RpcException $e)
            {
                http_response_code($e->getCode());
                print($e->getMessage());
                return;
            }

            $results = [];
            foreach($clientRequest->getRequests() as $rpcRequest)
            {
                $method = StandardMethods::tryFrom($rpcRequest->getMethod());

                if($method === false)
                {
                    $response = $rpcRequest->produceError(StandardError::RPC_METHOD_NOT_FOUND, 'The requested method does not exist');
                    if($response !== null)
                    {
                        $results[] = $response;
                    }
                }

                try
                {
                    $response = $method->execute($clientRequest, $rpcRequest);
                }
                catch(StandardException $e)
                {
                    $response = $e->produceError($rpcRequest);
                }
                catch(Exception $e)
                {
                    $response = $rpcRequest->produceError(StandardError::INTERNAL_SERVER_ERROR, 'An error occurred while processing the request');
                }

                if($response !== null)
                {
                    $results[] = $response;
                }
            }

            if(count($results) > 0)
            {
                print(json_encode($results));
                return;
            }

            http_response_code(204);
        }
    }