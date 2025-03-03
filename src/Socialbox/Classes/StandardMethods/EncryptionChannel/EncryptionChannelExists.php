<?php

    namespace Socialbox\Classes\StandardMethods\EncryptionChannel;

    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\EncryptionChannelManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class EncryptionChannelExists extends Method
    {

        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(!$rpcRequest->containsParameter('channel_uuid'))
            {
                throw new MissingRpcArgumentException('channel_uuid');
            }

            try
            {
                return $rpcRequest->produceResponse(EncryptionChannelManager::channelUuidExists($rpcRequest->getParameter('channel_uuid')));
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('An error occurred while checking if the channel exists', StandardError::INTERNAL_SERVER_ERROR, $e);
            }
        }
    }