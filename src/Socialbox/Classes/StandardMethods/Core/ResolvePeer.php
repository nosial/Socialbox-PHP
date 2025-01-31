<?php

    namespace Socialbox\Classes\StandardMethods\Core;

    use InvalidArgumentException;
    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\ReservedUsernames;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
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
                throw new MissingRpcArgumentException('peer');
            }

            // Parse the peer address
            try
            {
                $peerAddress = PeerAddress::fromAddress($rpcRequest->getParameter('peer'));
            }
            catch(InvalidArgumentException $e)
            {
                throw new StandardRpcException('Peer Address Error: ' . $e->getMessage(), StandardError::RPC_INVALID_ARGUMENTS, $e);
            }

            // Check if host is making the request & the identifier is not empty
            try
            {
                $identifyAs = null;
                if ($request->getPeer()->getUsername() == ReservedUsernames::HOST && $request->getIdentifyAs() != null)
                {
                    $identifyAs = $request->getIdentifyAs();
                }
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to retrieve peer information', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            // Resolve the peer using the server's peer resolver, this will resolve both internal peers and external peers
            return $rpcRequest->produceResponse(Socialbox::resolvePeer($peerAddress, $identifyAs));
        }
    }