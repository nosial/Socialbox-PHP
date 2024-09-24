<?php

    namespace Socialbox;

    use ConfigLib\Configuration;
    use Socialbox\Classes\RpcHandler;
    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\StandardMethods;
    use Socialbox\Exceptions\RpcException;

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

                $response = $method->execute($clientRequest, $rpcRequest);
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