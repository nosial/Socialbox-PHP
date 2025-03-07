<?php

    namespace Socialbox\Classes\StandardMethods\EncryptionChannel;

    use Exception;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Logger;
    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\Status\EncryptionChannelStatus;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\RpcException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\EncryptionChannelManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\Database\EncryptionChannelRecord;
    use Socialbox\Objects\RpcRequest;
    use Socialbox\Socialbox;

    class EncryptionChannelAcknowledgeMessage extends Method
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

            if(!$rpcRequest->containsParameter('message_uuid'))
            {
                throw new MissingRpcArgumentException('message_uuid');
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
                if ($request->isExternal())
                {
                    return self::handleExternal($request, $rpcRequest, $encryptionChannel);
                }
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to acknowledge the message', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return self::handleInternal($request, $rpcRequest, $encryptionChannel);
        }

        /**
         * Handles the external execution of the method.
         *
         * @param ClientRequest $request The client request instance.
         * @param RpcRequest $rpcRequest The RPC request instance.
         * @param EncryptionChannelRecord $encryptionChannel The encryption channel record.
         * @return SerializableInterface|null The response to the request.
         * @throws StandardRpcException If an error occurs.
         */
        public static function handleExternal(ClientRequest $request, RpcRequest $rpcRequest, EncryptionChannelRecord $encryptionChannel): ?SerializableInterface
        {
            if($request->getIdentifyAs() === null)
            {
                return $rpcRequest->produceError(StandardError::BAD_REQUEST, 'The IdentifyAs header is missing');
            }

            $requestingPeerAddress = $request->getIdentifyAs();
            if(!$encryptionChannel->isParticipant($requestingPeerAddress))
            {
                return $rpcRequest->produceError(StandardError::UNAUTHORIZED, 'The encryption channel is not accessible');
            }

            try
            {
                $message = EncryptionChannelManager::getMessageRecord($rpcRequest->getParameter('channel_uuid'), $rpcRequest->getParameter('message_uuid'));

                if($message === null)
                {
                    return $rpcRequest->produceError(StandardError::NOT_FOUND, 'The message does not exist');
                }

                if($message->getReceiver($encryptionChannel)->getAddress() !== $requestingPeerAddress)
                {
                    return $rpcRequest->produceError(StandardError::UNAUTHORIZED, 'The message is not for the requesting peer');
                }

                EncryptionChannelManager::acknowledgeMessage(
                    $rpcRequest->getParameter('channel_uuid'), $rpcRequest->getParameter('message_uuid')
                );
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to acknowledge the message', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse(true);
        }

        /**
         * Handles the internal execution of the method.
         *
         * @param ClientRequest $request The client request instance.
         * @param RpcRequest $rpcRequest The RPC request instance.
         * @param EncryptionChannelRecord $encryptionChannel The encryption channel record.
         * @return SerializableInterface|null The response to the request.
         * @throws StandardRpcException If an error occurs.
         */
        public static function handleInternal(ClientRequest $request, RpcRequest $rpcRequest, EncryptionChannelRecord $encryptionChannel): ?SerializableInterface
        {
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

            try
            {
                $message = EncryptionChannelManager::getMessageRecord($rpcRequest->getParameter('channel_uuid'), $rpcRequest->getParameter('message_uuid'));

                if($message === null)
                {
                    return $rpcRequest->produceError(StandardError::NOT_FOUND, 'The message does not exist');
                }

                if($message->getReceiver($encryptionChannel)->getAddress() !== $requestingPeer->getAddress())
                {
                    return $rpcRequest->produceError(StandardError::UNAUTHORIZED, 'The message is not for the requesting peer');
                }

                EncryptionChannelManager::acknowledgeMessage(
                    (string)$rpcRequest->getParameter('channel_uuid'), (string)$rpcRequest->getParameter('message_uuid')
                );
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to acknowledge the message', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            if($message->getOwner($encryptionChannel)->isExternal())
            {
                try
                {
                    $rpcClient = Socialbox::getExternalSession($message->getOwner($encryptionChannel)->getDomain());
                    $rpcClient->encryptionChannelAcknowledgeMessage(
                        channelUuid: (string)$rpcRequest->getParameter('channel_uuid'),
                        messageUuid: (string)$rpcRequest->getParameter('message_uuid'),
                        identifiedAs: $requestingPeer->getAddress()
                    );
                }
                catch(Exception $e)
                {
                    try
                    {
                        EncryptionChannelManager::rejectMessage($rpcRequest->getParameter('channel_uuid'), $rpcRequest->getParameter('message_uuid'), true);
                    }
                    catch (DatabaseOperationException $e)
                    {
                        Logger::getLogger()->error('Error rejecting message as server', $e);
                    }

                    if($e instanceof RpcException)
                    {
                        throw StandardRpcException::fromRpcException($e);
                    }

                    throw new StandardRpcException('Failed to acknowledge the message with the external server', StandardError::INTERNAL_SERVER_ERROR, $e);
                }
            }

            return $rpcRequest->produceResponse(true);
        }
    }