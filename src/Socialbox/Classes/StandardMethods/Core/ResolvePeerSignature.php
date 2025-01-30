<?php

    namespace Socialbox\Classes\StandardMethods\Core;

    use Exception;
    use InvalidArgumentException;
    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\RpcRequest;
    use Socialbox\Socialbox;
    use Symfony\Component\Uid\Uuid;

    class ResolvePeerSignature extends Method
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

            if(!$rpcRequest->containsParameter('uuid'))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Missing 'uuid' parameter");
            }

            try
            {
                $uuid = Uuid::fromString($rpcRequest->getParameter('uuid'));
            }
            catch(InvalidArgumentException $e)
            {
                throw new StandardRpcException('Invalid UUID', StandardError::RPC_INVALID_ARGUMENTS, $e);
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

            try
            {
                return $rpcRequest->produceResponse(Socialbox::resolvePeerSignature($peerAddress, $uuid->toRfc4122()));
            }
            catch(StandardRpcException $e)
            {
                throw $e;
            }
            catch (Exception $e)
            {
                throw new StandardRpcException('Failed to resolve peer signature', StandardError::INTERNAL_SERVER_ERROR, $e);
            }
        }
    }