<?php

    namespace Socialbox;

    use Exception;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\RpcHandler;
    use Socialbox\Classes\Utilities;
    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\StandardMethods;
    use Socialbox\Exceptions\RpcException;
    use Socialbox\Exceptions\StandardException;

    class Socialbox
    {
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
                }
                else
                {
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
                        if(Configuration::getConfiguration()['security']['display_internal_exceptions'])
                        {
                            $response = $rpcRequest->produceError(StandardError::INTERNAL_SERVER_ERROR, Utilities::throwableToString($e));
                        }
                        else
                        {
                            $response = $rpcRequest->produceError(StandardError::INTERNAL_SERVER_ERROR);
                        }
                    }
                }

                if($response !== null)
                {
                    $results[] = $response->toArray();
                }
            }

            if(count($results) == 0)
            {
                http_response_code(204);
                return;
            }

            if(count($results) == 1)
            {
                print(json_encode($results[0]));
                return;
            }

            print(json_encode($results));
        }
    }