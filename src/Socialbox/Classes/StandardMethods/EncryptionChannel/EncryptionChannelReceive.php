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
    use Socialbox\Objects\RpcRequest;
    use Socialbox\Socialbox;

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
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to retrieve the messages', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            if($acknowledge)
            {
                $messageUuids = array_map(fn($message) => $message->getUuid(), $messages);

                try
                {
                    EncryptionChannelManager::acknowledgeMessagesBatch(
                        channelUuid: $rpcRequest->getParameter('channel_uuid'),
                        messageUuids: $messageUuids,
                    );
                }
                catch (DatabaseOperationException $e)
                {
                    throw new StandardRpcException('Failed to acknowledge the messages locally', StandardError::INTERNAL_SERVER_ERROR, $e);
                }

                $externalPeer = $encryptionChannel->getExternalPeer();
                if($externalPeer !== null)
                {
                    try
                    {
                        $rpcClient = Socialbox::getExternalSession($externalPeer->getDomain());
                        $rpcClient->encryptionChannelAcknowledgeMessages(
                            channelUuid: (string)$rpcRequest->getParameter('channel_uuid'),
                            messageUuids: $messageUuids,
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
            }

            return $rpcRequest->produceResponse(array_map(fn($message) => $message->toStandard(), $messages));
        }
    }