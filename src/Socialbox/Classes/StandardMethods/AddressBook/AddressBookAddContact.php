<?php

    namespace Socialbox\Classes\StandardMethods\AddressBook;

    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\ReservedUsernames;
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
    use Socialbox\Socialbox;

    class AddressBookAddContact extends Method
    {
        /**
         * Adds a contact to the authenticated peer's address book, returns True if the contact was added
         * false if the contact already exists.
         *
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(!$rpcRequest->containsParameter('peer'))
            {
                throw new MissingRpcArgumentException('peer');
            }

            $peerAddress = PeerAddress::fromAddress($rpcRequest->getParameter('peer'));

            if($rpcRequest->containsParameter('relationship'))
            {
                $relationship = ContactRelationshipType::tryFrom(strtoupper($rpcRequest->getParameter('relationship')));
                if($relationship === null)
                {
                    throw new InvalidRpcArgumentException('relationship');
                }
            }
            else
            {
                $relationship = ContactRelationshipType::MUTUAL;
            }

            try
            {
                $peer = $request->getPeer();
                if($peer->getAddress() == $peerAddress)
                {
                    return $rpcRequest->produceError(StandardError::FORBIDDEN, 'Cannot add self as contact');
                }
                elseif($peer->getUsername() === ReservedUsernames::HOST->value)
                {
                    return $rpcRequest->produceError(StandardError::FORBIDDEN, 'Cannot add host as contact');
                }

                // Resolve the peer, this would throw a StandardException if something goes wrong
                Socialbox::resolvePeer($peerAddress);

                // Check if the contact already exists
                if(ContactManager::isContact($peer, $peerAddress))
                {
                    return $rpcRequest->produceError(StandardError::FORBIDDEN, 'Contact already exists');
                }

                // Create the contact
                $contactUuid = ContactManager::createContact($peer, $peerAddress, $relationship);
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to add contact', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            // Return success
            return $rpcRequest->produceResponse($contactUuid);
        }
    }