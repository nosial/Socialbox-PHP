<?php

    namespace Socialbox\Classes\StandardMethods;

    use InvalidArgumentException;
    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\Types\ContactRelationshipType;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\ContactManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\RpcRequest;
    use Socialbox\Socialbox;

    class AddressBookAddContact extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(!$rpcRequest->containsParameter('peer'))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'Missing required peer parameter');
            }

            try
            {
                $address = PeerAddress::fromAddress($rpcRequest->getParameter('peer'));
            }
            catch(InvalidArgumentException $e)
            {
                throw new StandardException('Invalid peer address', StandardError::RPC_INVALID_ARGUMENTS, $e);
            }

            if($rpcRequest->containsParameter('relationship'))
            {
                $relationship = ContactRelationshipType::tryFrom(strtoupper($rpcRequest->getParameter('relationship')));
                if($relationship === null)
                {
                    throw new StandardException('Invalid relationship type', StandardError::RPC_INVALID_ARGUMENTS);
                }
            }
            else
            {
                $relationship = ContactRelationshipType::MUTUAL;
            }

            try
            {
                // Resolve the peer, this would throw a StandardException if something goes wrong
                Socialbox::resolvePeer($address);

                // Check if the contact already exists
                $peer = $request->getPeer();
                if(ContactManager::isContact($peer, $address))
                {
                    return $rpcRequest->produceError(StandardError::FORBIDDEN, 'Contact already exists');
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