<?php

    namespace Socialbox\Classes\StandardMethods\EncryptionChannel;

    use Exception;
    use Socialbox\Abstracts\Method;
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

    class EncryptionDeleteChannel extends Method
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
            elseif(!$encryptionChannel->isParticipant($requestingPeer->getAddress()))
            {
                return $rpcRequest->produceError(StandardError::UNAUTHORIZED, 'The requested encryption channel is not accessible');
            }
            elseif($encryptionChannel->getStatus() === EncryptionChannelStatus::CLOSED)
            {
                return $rpcRequest->produceResponse(false);
            }

            try
            {
                EncryptionChannelManager::deleteChannel($encryptionChannel->getUuid());
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('An error occurred while trying to close the encryption channel', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            $externalPeer = $encryptionChannel->getExternalPeer();
            if($externalPeer !== null)
            {
                try
                {
                    $rpcClient = Socialbox::getExternalSession($encryptionChannel->getCallingPeerAddress()->getDomain());
                    $rpcClient->encryptionCloseChannel(
                        channelUuid: $rpcRequest->getParameter('channel_uuid'),
                        identifiedAs: $requestingPeer->getAddress()
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

            return $rpcRequest->produceResponse(true);
        }
    }