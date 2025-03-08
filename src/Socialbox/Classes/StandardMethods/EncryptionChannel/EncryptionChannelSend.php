<?php

    namespace Socialbox\Classes\StandardMethods\EncryptionChannel;

    use Exception;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Classes\Validator;
    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\Status\EncryptionChannelStatus;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\RpcException;
    use Socialbox\Exceptions\Standard\InvalidRpcArgumentException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\EncryptionChannelManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;
    use Socialbox\Socialbox;
    use Symfony\Component\Uid\Uuid;

    class EncryptionChannelSend extends Method
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
                if ($request->isExternal())
                {
                    return self::executeExternal($request, $rpcRequest);
                }
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('An error occurred while checking the request type', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return self::executeInternal($request, $rpcRequest);
        }

        /**
         * @param ClientRequest $request
         * @param RpcRequest $rpcRequest
         * @return SerializableInterface
         * @throws StandardRpcException
         */
        private static function executeInternal(ClientRequest $request, RpcRequest $rpcRequest): SerializableInterface
        {
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

            if(!$rpcRequest->containsParameter('checksum'))
            {
                throw new MissingRpcArgumentException('checksum');
            }

            if(!$rpcRequest->containsParameter('data'))
            {
                throw new MissingRpcArgumentException('data');
            }

            try
            {
                $messageUuid = Uuid::v4()->toRfc4122();
                $messageTimestamp = time();

                EncryptionChannelManager::sendMessage(
                    channelUuid: $channelUuid,
                    recipient: $encryptionChannel->determineRecipient($requestingPeer->getAddress()),
                    checksum: $rpcRequest->getParameter('checksum'),
                    data: $rpcRequest->getParameter('data'),
                    messageUuid: $messageUuid,
                    messageTimestamp: $messageTimestamp
                );
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to send the message', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            if($encryptionChannel->determineReceiver($requestingPeer->getAddress())->isExternal())
            {
                try
                {
                    $rpcClient = Socialbox::getExternalSession($encryptionChannel->determineReceiver($requestingPeer->getAddress())->getDomain());
                    $rpcClient->encryptionChannelSend(
                        channelUuid: $rpcRequest->getParameter('channel_uuid'),
                        checksum: $rpcRequest->getParameter('checksum'),
                        data: $rpcRequest->getParameter('data'),
                        identifiedAs: $requestingPeer->getAddress(),
                        messageUuid: $messageUuid,
                        timestamp: $messageTimestamp
                    );
                }
                catch(Exception $e)
                {
                    if($e instanceof RpcException)
                    {
                        throw StandardRpcException::fromRpcException($e);
                    }

                    throw new StandardRpcException('There was an error while trying to notify the external server of the encryption channel', StandardError::INTERNAL_SERVER_ERROR, $e);
                }
            }

            return $rpcRequest->produceResponse();
        }

        /**
         * @param ClientRequest $request
         * @param RpcRequest $rpcRequest
         * @return SerializableInterface
         * @throws StandardRpcException
         */
        private static function executeExternal(ClientRequest $request, RpcRequest $rpcRequest): SerializableInterface
        {
            if($request->getIdentifyAs() === null)
            {
                return $rpcRequest->produceError(StandardError::BAD_REQUEST, 'The IdentifyAs header is required');
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
            elseif(!$encryptionChannel->isParticipant($request->getIdentifyAs()))
            {
                return $rpcRequest->produceError(StandardError::UNAUTHORIZED, 'The encryption channel is not accessible');
            }

            if(!$rpcRequest->containsParameter('checksum'))
            {
                throw new MissingRpcArgumentException('checksum');
            }

            if(!$rpcRequest->containsParameter('data'))
            {
                throw new MissingRpcArgumentException('data');
            }

            if(!$rpcRequest->containsParameter('message_uuid'))
            {
                throw new MissingRpcArgumentException('message_uuid');
            }

            if(!$rpcRequest->containsParameter('timestamp'))
            {
                throw new MissingRpcArgumentException('timestamp');
            }
            elseif(!is_int($rpcRequest->getParameter('timestamp')))
            {
                throw new InvalidRpcArgumentException('timestamp', 'The given timestamp must be type integer');
            }

            try
            {
                EncryptionChannelManager::sendMessage(
                    channelUuid: $channelUuid,
                    recipient: $encryptionChannel->determineRecipient($request->getIdentifyAs()),
                    checksum: $rpcRequest->getParameter('checksum'),
                    data: $rpcRequest->getParameter('data'),
                    messageUuid: $rpcRequest->getParameter('message_uuid'),
                    messageTimestamp: (int)$rpcRequest->getParameter('timestamp')
                );
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to send the message', StandardError::INTERNAL_SERVER_ERROR, $e);
            }
            

            return $rpcRequest->produceResponse(true);
        }
    }