<?php

    namespace Socialbox\Classes\StandardMethods\Core;

    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\PeerInformationManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class GetSelf extends Method
    {

        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            try
            {
                $selfPeer = $request->getPeer();
                return $rpcRequest->produceResponse($selfPeer->toStandardPeer(PeerInformationManager::getFields($selfPeer)));
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Unable to resolve self peer', StandardError::INTERNAL_SERVER_ERROR, $e);
            }
        }
    }