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

    class SettingsSignatureExists extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(!$rpcRequest->containsParameter('uuid'))
            {
                throw new MissingRpcArgumentException('uuid');
            }

            try
            {
                $uuid = Uuid::fromString($rpcRequest->getParameter('uuid'));
            }
            catch(InvalidArgumentException)
            {
                throw new InvalidRpcArgumentException('uuid');
            }

            try
            {
                return $rpcRequest->produceResponse(SigningKeysManager::signingKeyExists($request->getPeer()->getUuid(), $uuid));
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to check the signing key existence', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

        }
    }