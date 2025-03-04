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
    use Socialbox\Objects\RpcRequest;

    class EncryptionChannelReceive extends Method
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

            $acknowledge = false;
            if($rpcRequest->containsParameter('acknowledge') && is_bool($rpcRequest->getParameter('acknowledge')) && $rpcRequest->getParameter('acknowledge'))
            {
                $acknowledge = true;
            }

            try
            {

                $messages = EncryptionChannelManager::receiveData(
                    $rpcRequest->getParameter('channel_uuid'), $encryptionChannel->determineRecipient($requestingPeer->getAddress(), true)
                );

                if($acknowledge)
                {
                    EncryptionChannelManager::acknowledgeMessagesBatch($rpcRequest->getParameter('channel_uuid'), array_map(fn($message) => $message->getUuid(), $messages));
                }
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to retrieve the messages', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse(array_map(fn($message) => $message->toStandard(), $messages));
        }
    }