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

    class SettingsGetSigningKey extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(!$rpcRequest->containsParameter('uuid') && $rpcRequest->getParameter('uuid') !== null)
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Missing 'uuid' parameter");
            }

            try
            {
                $key = SigningKeysManager::getSigningKey($request->getPeer()->getUuid(), $rpcRequest->getParameter('uuid'));
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to get the signing keys', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            if($key === null)
            {
                return $rpcRequest->produceError(StandardError::NOT_FOUND, 'The signing key does not exist');
            }

            // Return the signing key
            return $rpcRequest->produceResponse($key->toStandard());
        }
    }