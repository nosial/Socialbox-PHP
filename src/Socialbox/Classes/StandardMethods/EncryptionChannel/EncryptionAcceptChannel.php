<?php

    namespace Socialbox\Classes\StandardMethods\EncryptionChannel;

    use Exception;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Classes\Logger;
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

    class EncryptionAcceptChannel extends Method
    {

        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            try
            {
                if ($request->isExternal())
                {
                    return self::handleExternal($request, $rpcRequest);
                }

                return self::handleInternal($request, $rpcRequest);
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('An error occurred while checking the request type', StandardError::INTERNAL_SERVER_ERROR, $e);
            }
        }

        /**
         * @param ClientRequest $request
         * @param RpcRequest $rpcRequest
         * @return SerializableInterface|null
         * @throws StandardRpcException
         */
        private static function handleInternal(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(!$rpcRequest->containsParameter('channel_uuid'))
            {
                throw new MissingRpcArgumentException('channel_uuid');
            }
            elseif(!Validator::validateUuid($rpcRequest->getParameter('channel_uuid')))
            {
                throw new InvalidRpcArgumentException('channel_uuid', 'The given channel uuid is not a valid UUID V4');
            }

            if(!$rpcRequest->containsParameter('public_encryption_key'))
            {
                throw new MissingRpcArgumentException('public_encryption_key');
            }
            elseif(!Cryptography::validatePublicEncryptionKey('public_encryption_key'))
            {
                throw new InvalidRpcArgumentException('public_encryption_key', 'The given public encryption key is invalid');
            }

            try
            {
                $receivingPeer = $request->getPeer();
                $encryptionChannel = EncryptionChannelManager::getChannel($rpcRequest->getParameter('channel_uuid'));
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('There was an error while trying to obtain the encryption channel', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            if($encryptionChannel === null)
            {
                return $rpcRequest->produceError(StandardError::NOT_FOUND, 'The requested encryption channel was not found');
            }
            elseif($encryptionChannel->getReceivingPeerAddress()->getAddress() !== $receivingPeer->getAddress())
            {
                return $rpcRequest->produceError(StandardError::UNAUTHORIZED, 'The requested encryption channel is not accessible');
            }
            elseif($encryptionChannel->getStatus() !== EncryptionChannelStatus::AWAITING_RECEIVER)
            {
                return $rpcRequest->produceError(StandardError::FORBIDDEN, 'The encryption channel is not awaiting the receiver');
            }

            if($encryptionChannel->getCallingPeerAddress()->isExternal())
            {
                try
                {
                    $rpcClient = Socialbox::getExternalSession($encryptionChannel->getCallingPeerAddress()->getDomain());
                    $rpcClient->encryptionAcceptChannel(
                        channelUuid: $rpcRequest->getParameter('channel_uuid'),
                        publicEncryptionKey: $rpcRequest->getParameter('public_encryption_key'),
                        identifiedAs: $receivingPeer->getAddress()
                    );
                }
                catch(Exception $e)
                {
                    try
                    {
                        EncryptionChannelManager::declineChannel($rpcRequest->getParameter('channel_uuid'), true);
                    }
                    catch(DatabaseOperationException $e)
                    {
                        Logger::getLogger()->error('Error declining channel as server', $e);
                    }

                    if($e instanceof RpcException)
                    {
                        throw StandardRpcException::fromRpcException($e);
                    }

                    throw new StandardRpcException('There was an error while trying to notify the external server of the encryption channel', StandardError::INTERNAL_SERVER_ERROR, $e);
                }
            }

            try
            {
                EncryptionChannelManager::acceptChannel(
                    channelUuid: $rpcRequest->getParameter('channel_uuid'),
                    publicEncryptionKey: $rpcRequest->getParameter('public_encryption_key')
                );
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('There was an error while trying to accept the encryption channel', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse(true);
        }

        /**
         * @param ClientRequest $request
         * @param RpcRequest $rpcRequest
         * @return SerializableInterface|null
         * @throws StandardRpcException
         */
        private static function handleExternal(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if($request->getIdentifyAs() === null)
            {
                return $rpcRequest->produceError(StandardError::BAD_REQUEST, 'Missing required header IdentifyAs');
            }

            if(!$rpcRequest->containsParameter('channel_uuid'))
            {
                throw new MissingRpcArgumentException('channel_uuid');
            }
            elseif(!Validator::validateUuid($rpcRequest->getParameter('channel_uuid')))
            {
                throw new InvalidRpcArgumentException('channel_uuid', 'The given channel uuid is not a valid UUID V4');
            }

            if(!$rpcRequest->containsParameter('public_encryption_key'))
            {
                throw new MissingRpcArgumentException('public_encryption_key');
            }
            elseif(!Cryptography::validatePublicEncryptionKey('public_encryption_key'))
            {
                throw new InvalidRpcArgumentException('public_encryption_key', 'The given public encryption key is invalid');
            }

            try
            {
                $receivingPeer = Socialbox::resolvePeer($request->getIdentifyAs());
                $encryptionChannel = EncryptionChannelManager::getChannel($rpcRequest->getParameter('channel_uuid'));
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('There was an error while trying to obtain the encryption channel', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            if($encryptionChannel === null)
            {
                return $rpcRequest->produceError(StandardError::NOT_FOUND, 'The requested encryption channel was not found');
            }
            elseif($encryptionChannel->getReceivingPeerAddress() !== $receivingPeer->getPeerAddress())
            {
                return $rpcRequest->produceError(StandardError::UNAUTHORIZED, 'The requested encryption channel is not accessible');
            }
            elseif($encryptionChannel->getStatus() !== EncryptionChannelStatus::AWAITING_RECEIVER)
            {
                return $rpcRequest->produceError(StandardError::FORBIDDEN, 'The encryption channel is not awaiting the receiver');
            }

            try
            {
                EncryptionChannelManager::acceptChannel(
                    channelUuid: $rpcRequest->getParameter('channel_uuid'),
                    publicEncryptionKey: $rpcRequest->getParameter('public_encryption_key')
                );
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('There was an error while trying to accept the encryption channel', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse(true);
        }
    }