<?php

    namespace Socialbox\Classes\StandardMethods;

    use Exception;
    use InvalidArgumentException;
    use ncc\ThirdParty\Symfony\Uid\Uuid;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\SigningKeysManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class SettingsDeleteSignature extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(!$rpcRequest->containsParameter('uuid'))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Missing 'uuid' parameter");
            }

            try
            {
                $uuid = Uuid::fromString($rpcRequest->getParameter('uuid'));
            }
            catch(InvalidArgumentException $e)
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'Invalid UUID');
            }

            try
            {
                SigningKeysManager::deleteSigningKey($request->getPeer()->getUuid(), $uuid);
            }
            catch(Exception $e)
            {
                throw new StandardException('Failed to delete the signing key', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse(true);
        }
    }