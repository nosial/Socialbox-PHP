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

    class EncryptionGetChannel extends Method
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
                $requestingPeer = $request->getPeer();
                $encryptionChannel = EncryptionChannelManager::getChannel((string)$rpcRequest->getParameter('channel_uuid'));
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('There was an error while trying to obtain the encryption channel', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            if($encryptionChannel === null)
            {
                return $rpcRequest->produceError(StandardError::NOT_FOUND, 'The requested encryption channel was not found');
            }
            elseif(!$encryptionChannel->isParticipant($requestingPeer->getAddress()))
            {
                return $rpcRequest->produceError(StandardError::UNAUTHORIZED, 'The requested encryption channel is not accessible');
            }

            return $rpcRequest->produceResponse($encryptionChannel->toStandard());
        }
    }