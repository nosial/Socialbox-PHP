<?php

    namespace Socialbox\Classes\StandardMethods\Settings;

    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\SigningKeysManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class SettingsGetSigningKeys extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            try
            {
                $keys = SigningKeysManager::getSigningKeys($request->getPeer()->getUuid());
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to get the signing keys', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            if(empty($keys))
            {
                // Return an empty array if the results are empty
                return $rpcRequest->produceResponse([]);
            }

            // Return the signing keys as an array of standard objects
            return $rpcRequest->produceResponse(array_map(fn($key) => $key->toStandard(), $keys));
        }
    }