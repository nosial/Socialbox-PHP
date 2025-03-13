<?php

    namespace Socialbox\Classes\StandardMethods\AddressBook;

    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\ContactManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\RpcRequest;

    class AddressBookRevokeAllSignatures extends Method
    {
        /**
         * @inheritDoc
         * @noinspection DuplicatedCode
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(!$rpcRequest->containsParameter('peer'))
            {
                throw new MissingRpcArgumentException('peer');
            }

            $peerAddress = PeerAddress::fromAddress($rpcRequest->getParameter('peer'));

            try
            {
                // Check if the contact already exists
                $contact = ContactManager::getContact($request->getPeer(), $peerAddress);
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to check contact state with calling peer', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            if($contact === null)
            {
                return $rpcRequest->produceResponse(false);
            }

            try
            {
                ContactManager::removeAllContactSigningKeys($contact);
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to remove all contact signatures', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            // Return success
            return $rpcRequest->produceResponse(true);
        }
    }