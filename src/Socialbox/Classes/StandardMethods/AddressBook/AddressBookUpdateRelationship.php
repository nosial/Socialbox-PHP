<?php

    namespace Socialbox\Classes\StandardMethods\AddressBook;

    use InvalidArgumentException;
    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\Types\ContactRelationshipType;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\InvalidRpcArgumentException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\ContactManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\RpcRequest;

    class AddressBookUpdateRelationship extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(!$rpcRequest->containsParameter('peer'))
            {
                throw new MissingRpcArgumentException('peer');
            }

            $peerAddress = PeerAddress::fromAddress($rpcRequest->getParameter('peer'));

            if(!$rpcRequest->containsParameter('relationship'))
            {
                throw new MissingRpcArgumentException('relationship');
            }
            $newRelationship = ContactRelationshipType::tryFrom(strtoupper($rpcRequest->getParameter('relationship')));
            if($newRelationship === null)
            {
                throw new InvalidRpcArgumentException('relationship');
            }

            try
            {
                // Check if the contact already exists
                $requestingPeer = $request->getPeer();
                if(!ContactManager::isContact($requestingPeer->getUuid(), $peerAddress))
                {
                    return $rpcRequest->produceError(StandardError::FORBIDDEN, 'Contact does not exist');
                }

                // Create the contact
                ContactManager::updateContactRelationship($requestingPeer->getUuid(), $peerAddress, $newRelationship);
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to update contact relationship', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            // Return success
            return $rpcRequest->produceResponse(true);
        }
    }