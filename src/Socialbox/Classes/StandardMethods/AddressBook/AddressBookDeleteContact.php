<?php

    namespace Socialbox\Classes\StandardMethods\AddressBook;

    use InvalidArgumentException;
    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\InvalidRpcArgumentException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\ContactManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\RpcRequest;

    class AddressBookDeleteContact extends Method
    {
        /**
         * Deletes a contact from the authenticated peer's address book, returns True if the contact was deleted
         * false if the contact does not exist.
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

            try
            {
                // Check if the contact already exists
                $peer = $request->getPeer();
                if(!ContactManager::isContact($peer, $address))
                {
                    return $rpcRequest->produceResponse(false);
                }

                // Create the contact
                ContactManager::deleteContact($peer, $address);
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardException('Failed to remove contact', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            // Return success
            return $rpcRequest->produceResponse(true);
        }
    }