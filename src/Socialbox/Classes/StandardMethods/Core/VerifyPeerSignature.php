<?php

    namespace Socialbox\Classes\StandardMethods\Core;

    use Exception;
    use InvalidArgumentException;
    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\Standard\InvalidRpcArgumentException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\RpcRequest;
    use Socialbox\Socialbox;

    class VerifyPeerSignature extends Method
    {

        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            // Check if the required 'peer' parameter is set.
            if(!$rpcRequest->containsParameter('signing_peer'))
            {
                throw new MissingRpcArgumentException('signing_peer');
            }

            if(!$rpcRequest->containsParameter('signature_uuid'))
            {
                throw new MissingRpcArgumentException('signature_uuid');
            }

            if(!$rpcRequest->containsParameter('signature_key'))
            {
                throw new MissingRpcArgumentException('signature_key');
            }

            if(!$rpcRequest->containsParameter('signature'))
            {
                throw new MissingRpcArgumentException('signature');
            }

            if(!$rpcRequest->containsParameter('message_hash'))
            {
                throw new MissingRpcArgumentException('message_hash');
            }

            if(!$rpcRequest->containsParameter('signature_time'))
            {
                throw new MissingRpcArgumentException('signature_time');
            }

            // Parse the peer address
            try
            {
                $peerAddress = PeerAddress::fromAddress($rpcRequest->getParameter('signing_peer'));
            }
            catch(InvalidArgumentException $e)
            {
                throw new InvalidRpcArgumentException('signing_peer', $e);
            }

            try
            {
                return $rpcRequest->produceResponse(Socialbox::verifyPeerSignature(
                    signingPeer: $peerAddress,
                    signatureUuid: $rpcRequest->getParameter('signature_uuid'),
                    signatureKey: $rpcRequest->getParameter('signature_key'),
                    signature: $rpcRequest->getParameter('signature'),
                    messageHash: $rpcRequest->getParameter('message_hash'),
                    signatureTime: $rpcRequest->getParameter('signature_time')
                )->value);
            }
            catch (Exception $e)
            {
                throw new StandardRpcException('Failed to resolve peer signature', StandardError::INTERNAL_SERVER_ERROR, $e);
            }
        }
    }