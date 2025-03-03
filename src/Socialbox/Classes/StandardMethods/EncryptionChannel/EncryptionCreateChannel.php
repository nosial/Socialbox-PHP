<?php

    namespace Socialbox\Classes\StandardMethods\EncryptionChannel;

    use Exception;
    use InvalidArgumentException;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Classes\Logger;
    use Socialbox\Classes\Validator;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\RpcException;
    use Socialbox\Exceptions\Standard\InvalidRpcArgumentException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\EncryptionChannelManager;
    use Socialbox\Managers\RegisteredPeerManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\RpcRequest;
    use Socialbox\Socialbox;

    class EncryptionCreateChannel extends Method
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
            if(!$rpcRequest->containsParameter('receiving_peer'))
            {
                throw new MissingRpcArgumentException('receiving_peer');
            }
            elseif(!Validator::validatePeerAddress($rpcRequest->getParameter('receiving_peer')))
            {
                throw new InvalidRpcArgumentException('receiving_peer', 'Invalid Receiving Peer Address');
            }

            if(!$rpcRequest->containsParameter('public_encryption_key'))
            {
                throw new MissingRpcArgumentException('public_encryption_key');
            }
            elseif(!Cryptography::validatePublicEncryptionKey($rpcRequest->getParameter('public_encryption_key')))
            {
                throw new InvalidRpcArgumentException('public_encryption_key', 'The given public encryption key is invalid');
            }

            $receivingPeerAddress = PeerAddress::fromAddress($rpcRequest->getParameter('receiving_peer'));
            Socialbox::resolvePeer($receivingPeerAddress);

            try
            {
                $callingPeer = $request->getPeer();
                $callingPeerAddress = PeerAddress::fromAddress($callingPeer->getAddress());
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('There was an error while trying to obtain the calling peer', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            try
            {
                $uuid = EncryptionChannelManager::createChannel(
                    callingPeer: $callingPeerAddress,
                    receivingPeer: $receivingPeerAddress,
                    callingPublicEncryptionKey: $rpcRequest->getParameter('public_encryption_ke')
                );
            }
            catch(InvalidArgumentException $e)
            {
                throw new InvalidRpcArgumentException(null, $e);
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('There was an error while trying to create a new encryption channel', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            // If the receiver is in an external server, we must notify the external server as a client
            if($receivingPeerAddress->isExternal())
            {
                // Obtain the RPC Client, if for any reason it fails; we set the encryption channel as declined.
                try
                {
                    $rpcClient = Socialbox::getExternalSession($receivingPeerAddress->getDomain());
                    $externalUuid = $rpcClient->encryptionCreateChannel(
                        receivingPeer: $receivingPeerAddress,
                        publicEncryptionKey: $rpcRequest->getParameter('public_encryption_key'),
                        channelUuid: $uuid,
                        identifiedAs: $callingPeerAddress
                    );
                }
                catch(Exception $e)
                {
                    try
                    {
                        EncryptionChannelManager::declineChannel($uuid, true);
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

                // Check for sanity reasons
                if($externalUuid !== $uuid)
                {
                    try
                    {
                        EncryptionChannelManager::declineChannel($uuid, true);
                    }
                    catch(DatabaseOperationException $e)
                    {
                        Logger::getLogger()->error('Error declining channel as server', $e);
                    }

                    throw new StandardRpcException('The external server did not return the correct UUID', StandardError::UUID_MISMATCH);
                }
            }

            return null;
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
                return $rpcRequest->produceError(StandardError::BAD_REQUEST, 'Missing IdentifyAs request header');
            }

            $callingPeer = $request->getIdentifyAs();
            Socialbox::resolvePeer($callingPeer);

            if(!$rpcRequest->containsParameter('receiving_peer'))
            {
                throw new MissingRpcArgumentException('receiving_peer');
            }
            elseif(!Validator::validatePeerAddress($rpcRequest->getParameter('receiving_peer')))
            {
                throw new InvalidRpcArgumentException('receiving_peer', 'Invalid Receiving Peer Address');
            }

            if(!$rpcRequest->containsParameter('public_encryption_key'))
            {
                throw new MissingRpcArgumentException('public_encryption_key');
            }
            elseif(!Cryptography::validatePublicEncryptionKey($rpcRequest->getParameter('public_encryption_key')))
            {
                throw new InvalidRpcArgumentException('public_encryption_key', 'The given public encryption key is invalid');
            }

            // Check for an additional required parameter 'channel_uuid'
            if(!$rpcRequest->containsParameter('channel_uuid'))
            {
                throw new MissingRpcArgumentException('channel_uuid');
            }
            elseif(!Validator::validateUuid($rpcRequest->getParameter('channel_uuid')))
            {
                throw new InvalidRpcArgumentException('channel_uuid', 'The given UUID is not a valid UUID v4 format');
            }

            // Check if the UUID already is used on this server
            try
            {
                if(EncryptionChannelManager::channelUuidExists($rpcRequest->getParameter('channel_uuid')))
                {
                    return $rpcRequest->produceError(StandardError::UUID_CONFLICT, 'The given UUID already exists with another existing encryption channel on this server');
                }
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('There was an error while checking the existence of the channel UUID', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            $receivingPeerAddress = PeerAddress::fromAddress($rpcRequest->getParameter('receiving_peer'));
            if($receivingPeerAddress->isExternal())
            {
                return $rpcRequest->produceError(StandardError::PEER_NOT_FOUND, 'The receiving peer does not belong to this server');
            }

            try
            {
                $receivingPeer = RegisteredPeerManager::getPeerByAddress($rpcRequest->getParameter('receiving_peer'));
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('There was an error while trying to obtain the receiving peer', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            if($receivingPeer === null)
            {
                return $rpcRequest->produceError(StandardError::PEER_NOT_FOUND, 'The receiving peer does not exist on this server');
            }

            try
            {
                $uuid = EncryptionChannelManager::createChannel(
                    callingPeer: $callingPeer,
                    receivingPeer: $receivingPeerAddress,
                    callingPublicEncryptionKey: $rpcRequest->getParameter('public_encryption_key'),
                    channelUUid: $rpcRequest->getParameter('channel_uuid')
                );
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('There was an error while trying to create the encryption channel', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            if($uuid !== $rpcRequest->getParameter('channel_uuid'))
            {
                try
                {
                    EncryptionChannelManager::declineChannel($rpcRequest->getParameter('channel_uuid'), true);
                }
                catch(DatabaseOperationException $e)
                {
                    Logger::getLogger()->error('There was an error while trying to decline the encryption channel as a server', $e);
                }

                return $rpcRequest->produceError(StandardError::UUID_MISMATCH, 'The created UUID in the server does not match the UUID that was received');
            }

            return $rpcRequest->produceResponse($uuid);
        }
    }