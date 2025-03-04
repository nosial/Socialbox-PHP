<?php

    namespace Socialbox\Classes\StandardMethods\EncryptionChannel;

    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Validator;
    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\Status\EncryptionChannelStatus;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\InvalidRpcArgumentException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\EncryptionChannelManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\Database\EncryptionChannelRecord;
    use Socialbox\Objects\RpcRequest;

    class EncryptionChannelAcknowledge extends Method
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
            elseif(!Validator::validateUuid($rpcRequest->getParameter('channel_uuid')))
            {
                throw new InvalidRpcArgumentException('channel_uuid', 'The given channel uuid is not a valid UUID V4');
            }

            try
            {
                $channelUuid = $rpcRequest->getParameter('channel_uuid');
                $encryptionChannel = EncryptionChannelManager::getChannel($channelUuid);
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to retrieve the encryption channel', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            if($encryptionChannel === null)
            {
                return $rpcRequest->produceError(StandardError::NOT_FOUND, 'The encryption channel does not exist');
            }

            try
            {
                $requestingPeer = $request->getPeer();
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to retrieve the peer', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            if($requestingPeer === null)
            {
                return $rpcRequest->produceError(StandardError::UNAUTHORIZED, 'The peer is not authorized');
            }

            if(!$encryptionChannel->isParticipant($requestingPeer->getAddress()))
            {
                return $rpcRequest->produceError(StandardError::UNAUTHORIZED, 'The encryption channel is not accessible');
            }
            elseif($encryptionChannel->getStatus() !== EncryptionChannelStatus::OPENED)
            {
                return $rpcRequest->produceError(StandardError::FORBIDDEN, 'The encryption channel is not opened');
            }

            if(!$rpcRequest->containsParameter('message_uuid'))
            {
                throw new MissingRpcArgumentException('message_uuid');
            }

            if(is_array($rpcRequest->getParameter('message_uuid')))
            {
                return self::handleMultipleMessages($rpcRequest, $encryptionChannel);
            }
            elseif(is_string($rpcRequest->getParameter('message_uuid')))
            {
                return self::handleSingleMessage($rpcRequest, $encryptionChannel);
            }

            return $rpcRequest->produceError(StandardError::BAD_REQUEST, 'The message_uuid parameter must be a string or an array of strings');
        }

        private static function handleSingleMessage(RpcRequest $rpcRequest, EncryptionChannelRecord $encryptionChannel)
        {
            if(!Validator::validateUuid($rpcRequest->getParameter('message_uuid')))
            {
                throw new InvalidRpcArgumentException('message_uuid', 'The given message uuid is not a valid UUID V4');
            }

            try
            {
                EncryptionChannelManager::acknowledgeMessage($encryptionChannel->getUuid(), $rpcRequest->getParameter('message_uuid'));
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to acknowledge the message', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse(true);
        }

        private static function handleMultipleMessages(RpcRequest $rpcRequest, EncryptionChannelRecord $encryptionChannel)
        {
            $messageUuids = $rpcRequest->getParameter('message_uuid');

            foreach($messageUuids as $messageUuid)
            {
                if(!Validator::validateUuid($messageUuid))
                {
                    return $rpcRequest->produceError(StandardError::BAD_REQUEST, sprintf('The message uuid %s is not a valid UUID V4', $messageUuid));
                }
            }

            try
            {
                EncryptionChannelManager::acknowledgeMessagesBatch($encryptionChannel->getUuid(), $messageUuids);
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to acknowledge the messages', StandardError::INTERNAL_SERVER_ERROR, $e);
            }


            return $rpcRequest->produceResponse(true);
        }
    }