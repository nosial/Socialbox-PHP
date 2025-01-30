<?php

    namespace Socialbox\Classes\StandardMethods\AddressBook;

    use InvalidArgumentException;
    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\Types\ContactRelationshipType;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\InvalidRpcArgumentException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardException;
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

            try
            {
                $address = PeerAddress::fromAddress($rpcRequest->getParameter('peer'));
            }
            catch(InvalidArgumentException $e)
            {
                throw new InvalidRpcArgumentException('peer', $e->getMessage());
            }

            if($rpcRequest->containsParameter('relationship'))
            {
                $relationship = ContactRelationshipType::tryFrom(strtoupper($rpcRequest->getParameter('relationship')));
                if($relationship === null)
                {
                    throw new InvalidRpcArgumentException('peer', 'Invalid relationship type');
                }
            }
            else
            {
                $relationship = ContactRelationshipType::MUTUAL;
            }

            try
            {
                $peer = $request->getPeer();
                if($peer->getAddress() == $address)
                {
                    return $rpcRequest->produceError(StandardError::FORBIDDEN, 'Cannot add self as contact');
                }

                // Resolve the peer, this would throw a StandardException if something goes wrong
                Socialbox::resolvePeer($address);

                // Check if the contact already exists
                if(ContactManager::isContact($peer, $address))
                {
                    return $rpcRequest->produceResponse(false);
                }

                // Create the contact
                ContactManager::createContact($peer, $address, $relationship);
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardException('Failed to add contact', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            // Return success
            return $rpcRequest->produceResponse(true);
        }
    }