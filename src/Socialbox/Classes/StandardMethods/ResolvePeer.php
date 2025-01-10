<?php

    namespace Socialbox\Classes\StandardMethods;

    use Exception;
    use InvalidArgumentException;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\RpcException;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\RegisteredPeerManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\RpcRequest;
    use Socialbox\Socialbox;

    class ResolvePeer extends Method
    {

        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            // Check if the required 'peer' parameter is set.
            if(!$rpcRequest->containsParameter('peer'))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Missing 'peer' parameter");
            }

            // Parse the peer address
            try
            {
                $peerAddress = PeerAddress::fromAddress($rpcRequest->getParameter('peer'));
            }
            catch(InvalidArgumentException $e)
            {
                throw new StandardException('Peer Address Error: ' . $e->getMessage(), StandardError::RPC_INVALID_ARGUMENTS, $e);
            }

            // If the requested peer resides in the server, resolve the peer internally.
            if($peerAddress->getDomain() === Configuration::getInstanceConfiguration()->getDomain())
            {
                try
                {
                    $registeredPeer = RegisteredPeerManager::getPeerByAddress($peerAddress);
                }
                catch (DatabaseOperationException $e)
                {
                    throw new StandardException('There was an unexpected error while trying to resolve the peer internally', StandardError::INTERNAL_SERVER_ERROR, $e);
                }

                // Return not found if the returned record is null or if the registered peer isn't enabled
                if($registeredPeer === null || !$registeredPeer->isEnabled())
                {
                    return $rpcRequest->produceError(StandardError::PEER_NOT_FOUND, sprintf('Peer %s not found', $peerAddress->getAddress()));
                }

                // Return standard peer representation
                return $rpcRequest->produceResponse($registeredPeer->toStandardPeer());
            }

            // Otherwise, resolve the peer from the remote server
            try
            {
                $client = Socialbox::getExternalSession($peerAddress->getDomain());
            }
            catch(Exception $e)
            {
                throw new StandardException(sprintf('There was an error while trying to connect to %s: %s', $peerAddress->getDomain(), $e->getMessage()), StandardError::RESOLUTION_FAILED, $e);
            }

            // Return the result/error of the resolution
            try
            {
                return $rpcRequest->produceResponse($client->resolvePeer($peerAddress));
            }
            catch(RpcException $e)
            {
                throw new StandardException($e->getMessage(), StandardError::tryFrom($e->getCode()) ?? StandardError::UNKNOWN, $e);
            }
        }
    }