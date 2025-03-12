<?php

    namespace Socialbox\Classes\StandardMethods\Settings;

    use Exception;
    use InvalidArgumentException;
    use ncc\ThirdParty\Symfony\Uid\Uuid;
    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\InvalidRpcArgumentException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
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
            if(!$rpcRequest->containsParameter('signature_uuid'))
            {
                throw new MissingRpcArgumentException('signature_uuid');
            }

            $signatureUuid = (string)$rpcRequest->getParameter('signature_uuid');

            try
            {
                if(!SigningKeysManager::signingKeyExists($request->getPeer()->getUuid(), $signatureUuid))
                {
                    return $rpcRequest->produceResponse(false);
                }

                SigningKeysManager::deleteSigningKey($request->getPeer()->getUuid(), $signatureUuid);
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to delete the signing key', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse(true);
        }
    }