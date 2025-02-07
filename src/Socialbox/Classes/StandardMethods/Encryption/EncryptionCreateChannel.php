<?php

    namespace Socialbox\Classes\StandardMethods\Encryption;

    use Exception;
    use InvalidArgumentException;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Classes\Validator;
    use Socialbox\Enums\ReservedUsernames;
    use Socialbox\Enums\SigningKeyState;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\InvalidRpcArgumentException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\EncryptionChannelManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\RpcRequest;
    use Socialbox\Objects\Standard\SigningKey;
    use Socialbox\Socialbox;

    class EncryptionCreateChannel extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            // Check the calling peer, if a server is making the request, it must be identified
            // Otherwise, we assume the authenticated user is the calling peer
            // But a server must provide a UUID. This is to prevent a user from creating a channel with a UUID
            $callingPeer = self::getCallingPeer($request, $rpcRequest);
            $callingPeerSignature = self::getCallingSignature($callingPeer, $rpcRequest);
            $receivingPeer = self::getReceivingPeer($rpcRequest);
            $receivingPeerSignature = self::getReceivingSignature($receivingPeer, $rpcRequest);
            $channelUuid = self::getChannelUuid($request, $rpcRequest);

            // Verify the calling encryption public key
            if(!$rpcRequest->containsParameter('calling_encryption_public_key'))
            {
                throw new MissingRpcArgumentException('calling_encryption_public_key');
            }
            if(!Cryptography::validatePublicEncryptionKey($rpcRequest->getParameter('calling_encryption_public_key')))
            {
                throw new InvalidRpcArgumentException('calling_encryption_public_key', 'Invalid calling encryption public key');
            }

            // Transport Algorithm Validation
            if(!$rpcRequest->containsParameter('transport_encryption_algorithm'))
            {
                throw new MissingRpcArgumentException('transport_encryption_algorithm');
            }
            if(!Cryptography::isSupportedAlgorithm($rpcRequest->getParameter('transport_encryption_algorithm')))
            {
                throw new InvalidRpcArgumentException('transport_encryption_algorithm', 'Unsupported Transport Encryption Algorithm');
            }

            // Create/Import the encryption channel
            try
            {
                $channelUuid = EncryptionChannelManager::createChannel(
                    callingPeer: $callingPeer,
                    receivingPeer: $receivingPeer,
                    signatureUuid: $callingPeerSignature->getUuid(),
                    signingPublicKey: $callingPeerSignature->getPublicKey(),
                    encryptionPublicKey: $rpcRequest->getParameter('calling_encryption_public_key'),
                    transportEncryptionAlgorithm: $rpcRequest->getParameter('transport_encryption_algorithm'),
                    uuid: $channelUuid
                );
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to create the encryption channel', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            // If the receiving peer resides on an external server, then we need to tell the external server
            // about the encryption channel so that the receiving peer can see it.
            if($receivingPeer->getDomain() !== Configuration::getInstanceConfiguration()->getDomain())
            {
                $rpcClient = Socialbox::getExternalSession($receivingPeer->getDomain());

            }

            return $rpcRequest->produceResponse($channelUuid);
        }

        /**
         * Returns the PeerAddress of the calling peer, if a server is making a request then the server must provide
         * both the UUID of the encryption channel and the PeerAddress of the calling peer to prevent UUID conflicts
         *
         * Otherwise, the calling peer is assumed to be the authenticated user and no UUID is required
         *
         * @param ClientRequest $request The full client request
         * @param RpcRequest $rpcRequest The focused RPC request
         * @return PeerAddress The calling peer
         * @throws StandardRpcException If the calling peer cannot be resolved
         */
        private static function getCallingPeer(ClientRequest $request, RpcRequest $rpcRequest): PeerAddress
        {
            if($request->getIdentifyAs() !== null)
            {
                try
                {
                    // Prevent UUID conflicts if the server is trying to use an UUID that already exists on this server
                    if (EncryptionChannelManager::channelExists($rpcRequest->getParameter('uuid')))
                    {
                        throw new StandardRpcException('UUID Conflict, a channel with this UUID already exists', StandardError::UUID_CONFLICT);
                    }
                }
                catch (DatabaseOperationException $e)
                {
                    throw new StandardRpcException('Failed to resolve channel UUID', StandardError::INTERNAL_SERVER_ERROR, $e);
                }

                if($request->getIdentifyAs()->getUsername() == ReservedUsernames::HOST)
                {
                    throw new StandardRpcException('The identifier cannot be a host', StandardError::BAD_REQUEST);
                }

                if($request->getIdentifyAs()->getDomain() !== Configuration::getInstanceConfiguration()->getDomain())
                {
                    Socialbox::resolvePeer($request->getIdentifyAs());
                }

                return $request->getIdentifyAs();
            }

            try
            {
                return PeerAddress::fromAddress($request->getPeer()->getAddress());
            }
            catch(StandardRpcException $e)
            {
                throw $e;
            }
            catch(Exception $e)
            {
                throw new StandardRpcException('The calling peer cannot be resolved', StandardError::INTERNAL_SERVER_ERROR, $e);
            }
        }

        /**
         * Resolves and returns the calling peer's signing key, if the calling peer is coming from an external server
         * then the signature returned is the resolved signature from the external server, otherwise the signature
         * is locally resolved and returned
         *
         * @param PeerAddress $callingPeer The calling peer
         * @param RpcRequest $rpcRequest The focused RPC request
         * @return SigningKey The resolved signing key
         * @throws InvalidRpcArgumentException If one or more RPC parameters are invalid
         * @throws MissingRpcArgumentException If one or more RPC parameters are missing
         * @throws StandardRpcException If the calling signature cannot be resolved
         */
        private static function getCallingSignature(PeerAddress $callingPeer, RpcRequest $rpcRequest): SigningKey
        {
            // Caller signature verification
            if(!$rpcRequest->containsParameter('calling_signature_uuid'))
            {
                throw new MissingRpcArgumentException('calling_signature_uuid');
            }
            if(!Validator::validateUuid($rpcRequest->getParameter('calling_signature_uuid')))
            {
                throw new InvalidRpcArgumentException('calling_signature_uuid', 'Invalid UUID V4');
            }
            if(!$rpcRequest->containsParameter('calling_signature_public_key'))
            {
                throw new MissingRpcArgumentException('calling_signature_public_key');
            }
            if(!Cryptography::validatePublicSigningKey($rpcRequest->getParameter('calling_signature_public_key')))
            {
                throw new InvalidRpcArgumentException('calling_signature_public_key', 'Invalid Public Key');
            }

            // Resolve the signature
            $resolvedCallingSignature = Socialbox::resolvePeerSignature($callingPeer, $rpcRequest->getParameter('calling_signature_uuid'));
            if($resolvedCallingSignature->getPublicKey() !== $rpcRequest->getParameter('calling_signature_public_key'))
            {
                throw new InvalidRpcArgumentException('calling_signature_public_key', 'Public signing key of the calling peer does not match the resolved signature');
            }
            if($resolvedCallingSignature->getState() === SigningKeyState::EXPIRED)
            {
                throw new StandardRpcException('The public signing key of the calling peer has expired', StandardError::EXPIRED);
            }

            $resolvedSignature = Socialbox::resolvePeerSignature($callingPeer, $rpcRequest->getParameter('calling_signature_uuid'));
            if($resolvedSignature === null)
            {
                throw new StandardRpcException('The calling peer signature could not be resolved', StandardError::NOT_FOUND);
            }

            return $resolvedSignature;
        }

        /**
         * Returns the PeerAddress of the receiving peer, if the receiving peer is from an external server then the
         * receiving peer is resolved and returned, otherwise the receiving peer is locally resolved and returned
         *
         * @param RpcRequest $rpcRequest The focused RPC request
         * @return PeerAddress The receiving peer
         * @throws InvalidRpcArgumentException If one or more RPC parameters are invalid
         * @throws MissingRpcArgumentException If one or more RPC parameters are missing
         * @throws StandardRpcException If the receiving peer cannot be resolved
         */
        private static function getReceivingPeer(RpcRequest $rpcRequest): PeerAddress
        {
            if(!$rpcRequest->containsParameter('receiving_peer'))
            {
                throw new MissingRpcArgumentException('receiving_peer');
            }

            try
            {
                $receivingPeer = PeerAddress::fromAddress($rpcRequest->getParameter('receiving_peer'));
            }
            catch(InvalidArgumentException $e)
            {
                throw new InvalidRpcArgumentException('receiving_peer', $e);
            }

            if($receivingPeer->getUsername() == ReservedUsernames::HOST)
            {
                throw new InvalidRpcArgumentException('receiving_peer', 'Hosts cannot receive channels');
            }

            // Resolve the receiving peer if it's from an external server
            if($receivingPeer->getDomain() !== Configuration::getInstanceConfiguration()->getDomain())
            {
                Socialbox::resolvePeer($receivingPeer);
            }

            return $receivingPeer;
        }

        /**
         * @param PeerAddress $receivingPeer
         * @param RpcRequest $rpcRequest
         * @return SigningKey
         * @throws InvalidRpcArgumentException
         * @throws MissingRpcArgumentException
         * @throws StandardRpcException
         */
        private static function getReceivingSignature(PeerAddress $receivingPeer, RpcRequest $rpcRequest): SigningKey
        {
            // Receiving signature verification
            if(!$rpcRequest->containsParameter('receiving_signature_uuid'))
            {
                throw new MissingRpcArgumentException('receiving_signature_uuid');
            }
            if(!Validator::validateUuid($rpcRequest->getParameter('receiving_signature_uuid')))
            {
                throw new InvalidRpcArgumentException('receiving_signature_uuid', 'Invalid UUID V4');
            }
            if(!$rpcRequest->containsParameter('receiving_signature_public_key'))
            {
                throw new MissingRpcArgumentException('receiving_signature_public_key');
            }
            if(!Cryptography::validatePublicSigningKey($rpcRequest->getParameter('receiving_signature_public_key')))
            {
                throw new InvalidRpcArgumentException('receiving_signature_public_key', 'Invalid Public Key');
            }

            // Resolve the signature
            $resolvedReceivingSignature = Socialbox::resolvePeerSignature($receivingPeer, $rpcRequest->getParameter('receiving_signature_uuid'));
            if($resolvedReceivingSignature->getPublicKey() !== $rpcRequest->getParameter('receiving_signature_public_key'))
            {
                throw new InvalidRpcArgumentException('receiving_signature_public_key', 'Public signing key of the receiving peer does not match the resolved signature');
            }
            if($resolvedReceivingSignature->getState() === SigningKeyState::EXPIRED)
            {
                throw new StandardRpcException('The public signing key of the receiving peer has expired', StandardError::EXPIRED);
            }

            $resolvedSignature = Socialbox::resolvePeerSignature($receivingPeer, $rpcRequest->getParameter('receiving_signature_uuid'));
            if($resolvedSignature === null)
            {
                throw new StandardRpcException('The receiving peer signature could not be resolved', StandardError::NOT_FOUND);
            }

            return $resolvedSignature;
        }

        /**
         * @param ClientRequest $request
         * @param RpcRequest $rpcRequest
         * @return string|null
         * @throws InvalidRpcArgumentException
         * @throws MissingRpcArgumentException
         */
        private static function getChannelUuid(ClientRequest $request, RpcRequest $rpcRequest): ?string
        {
            if($request->getIdentifyAs() !== null)
            {
                if(!$rpcRequest->containsParameter('uuid'))
                {
                    throw new MissingRpcArgumentException('uuid');
                }

                if(!Validator::validateUuid($rpcRequest->getParameter('uuid')))
                {
                    throw new InvalidRpcArgumentException('uuid', 'Invalid UUID V4');
                }

                if(EncryptionChannelManager::channelExists($rpcRequest->getParameter('uuid')))
                {
                    throw new StandardRpcException('UUID Conflict, a channel with this UUID already exists', StandardError::UUID_CONFLICT);
                }

                return $rpcRequest->getParameter('uuid');
            }

            return null;
        }
    }