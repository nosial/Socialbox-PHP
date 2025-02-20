<?php

    namespace Socialbox\Classes\StandardMethods\Core;

    use InvalidArgumentException;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Classes\Validator;
    use Socialbox\Exceptions\Standard\InvalidRpcArgumentException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\RpcRequest;
    use Socialbox\Socialbox;

    class VerifySignature extends Method
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

            if(!$rpcRequest->containsParameter('signature_uuid'))
            {
                throw new MissingRpcArgumentException('signature_uuid');
            }
            elseif(!Validator::validateUuid($rpcRequest->getParameter('signature_uuid')))
            {
                throw new InvalidRpcArgumentException('signature_uuid', 'Invalid UUID V4');
            }

            if(!$rpcRequest->containsParameter('signature'))
            {
                throw new MissingRpcArgumentException('signature');
            }

            if(!$rpcRequest->containsParameter('sha512'))
            {
                throw new MissingRpcArgumentException('sha512');
            }
            elseif(!Cryptography::validateSha512($rpcRequest->getParameter('sha512')))
            {
                throw new InvalidRpcArgumentException('sha512', 'Invalid SHA512');
            }

            // Parse the peer address
            try
            {
                $peerAddress = PeerAddress::fromAddress($rpcRequest->getParameter('peer'));
            }
            catch(InvalidArgumentException $e)
            {
                throw new InvalidRpcArgumentException('peer', $e);
            }

            if($rpcRequest->containsParameter('time'))
            {
                if(!is_numeric($rpcRequest->getParameter('time')))
                {
                    throw new InvalidRpcArgumentException('time', 'Invalid timestamp, must be a Unix Timestamp');
                }

                return $rpcRequest->produceResponse(Socialbox::verifyTimedSignature(
                    signingPeer: $peerAddress,
                    signatureUuid: $rpcRequest->getParameter('signature_uuid'),
                    signature: $rpcRequest->getParameter('signature'),
                    messageHash: $rpcRequest->getParameter('sha512'),
                    signatureTime: (int)$rpcRequest->getParameter('time')
                )->value);
            }

            return $rpcRequest->produceResponse(Socialbox::verifySignature(
                signingPeer: $peerAddress,
                signatureUuid: $rpcRequest->getParameter('signature_uuid'),
                signature: $rpcRequest->getParameter('signature'),
                messageHash: $rpcRequest->getParameter('sha512'),
            )->value);
        }
    }