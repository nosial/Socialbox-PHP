<?php

    namespace Socialbox;

    use Exception;
    use LogLib\Log;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Logger;
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
                Logger::getLogger()->error('Failed to parse the client request', $e);
                http_response_code($e->getCode());
                print($e->getMessage());
                return;
            }

            Logger::getLogger()->verbose(sprintf('Received %d RPC request(s) from %s', count($clientRequest->getRequests()), $_SERVER['REMOTE_ADDR']));

            $results = [];
            foreach($clientRequest->getRequests() as $rpcRequest)
            {
                $method = StandardMethods::tryFrom($rpcRequest->getMethod());

                if($method === false)
                {
                    Logger::getLogger()->warning('The requested method does not exist');
                    $response = $rpcRequest->produceError(StandardError::RPC_METHOD_NOT_FOUND, 'The requested method does not exist');
                }
                else
                {
                    try
                    {
                        Logger::getLogger()->debug(sprintf('Processing RPC request for method %s', $rpcRequest->getMethod()));
                        $response = $method->execute($clientRequest, $rpcRequest);
                    }
                    catch(StandardException $e)
                    {
                        Logger::getLogger()->error('An error occurred while processing the RPC request', $e);
                        $response = $e->produceError($rpcRequest);
                    }
                    catch(Exception $e)
                    {
                        Logger::getLogger()->error('An internal error occurred while processing the RPC request', $e);
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
                    Logger::getLogger()->debug(sprintf('Producing response for method %s', $rpcRequest->getMethod()));
                    $results[] = $response->toArray();
                }
            }

            if(count($results) == 0)
            {
                Logger::getLogger()->verbose('No results to return');
                http_response_code(204);
                return;
            }

            if(count($results) == 1)
            {
                Logger::getLogger()->verbose('Returning single result');
                print(json_encode($results[0]));
                return;
            }

            Logger::getLogger()->verbose('Returning multiple results');
            print(json_encode($results));
        }
    }