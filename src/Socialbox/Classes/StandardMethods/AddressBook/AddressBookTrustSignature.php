<?php

    namespace Socialbox\Classes\StandardMethods\AddressBook;

    use InvalidArgumentException;
    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\ContactManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\RpcRequest;
    use Symfony\Component\Uid\Uuid;

    class AddressBookTrustSignature extends Method
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
                throw new StandardException('Invalid UUID', StandardError::RPC_INVALID_ARGUMENTS, $e);
            }

            try
            {
                // Check if the contact already exists
                $peer = $request->getPeer();
                if(ContactManager::isContact($peer, $address))
                {

                }

                // Create the contact
                ContactManager::updateContactRelationship($peer, $address, $relationship);
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardException('Failed to update contact relationship', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            // Return success
            return $rpcRequest->produceResponse(true);
        }
    }