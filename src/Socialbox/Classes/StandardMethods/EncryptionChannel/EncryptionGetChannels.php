<?php

    namespace Socialbox\Classes\StandardMethods\EncryptionChannel;

    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Validator;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\InvalidRpcArgumentException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\EncryptionChannelManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class EncryptionGetChannels extends Method
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

            $page = 1;
            $limit = Configuration::getPoliciesConfiguration()->getEncryptionChannelsLimit();
            if($rpcRequest->containsParameter('page'))
            {
                $page = (int)$rpcRequest->getParameter('page');
            }

            if($rpcRequest->containsParameter('limit'))
            {
                $limit = (int)$rpcRequest->getParameter('limit');
            }

            try
            {
                $requestingPeer = $request->getPeer();
                return $rpcRequest->produceResponse(array_map(function($channel) use ($requestingPeer)
                {
                    return $channel->toStandard();
                }, EncryptionChannelManager::getChannels($requestingPeer->getAddress(), $page, $limit)));
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('There was an error while trying to obtain the encryption channels', StandardError::INTERNAL_SERVER_ERROR, $e);
            }
        }
    }