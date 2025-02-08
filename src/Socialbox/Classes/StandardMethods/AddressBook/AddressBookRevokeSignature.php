<?php

    namespace Socialbox\Classes\StandardMethods\AddressBook;

    use InvalidArgumentException;
    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\InvalidRpcArgumentException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\ContactManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\RpcRequest;
    use Symfony\Component\Uid\Uuid;

    class AddressBookRevokeSignature extends Method
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

            try
            {
                $address = PeerAddress::fromAddress($rpcRequest->getParameter('peer'));
            }
            catch(InvalidArgumentException $e)
            {
                throw new InvalidRpcArgumentException('peer', $e);
            }

            if(!$rpcRequest->containsParameter('signature_uuid'))
            {
                throw new MissingRpcArgumentException('signature_uuid');
            }

            try
            {
                $signatureUuid = Uuid::fromString($rpcRequest->getParameter('signature_uuid'));
            }
            catch(InvalidArgumentException $e)
            {
                throw new InvalidRpcArgumentException('signature_uuid', $e);
            }

            try
            {
                // Check if the contact already exists
                $peer = $request->getPeer();
                $contact = ContactManager::getContact($peer, $address);
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
                if(!ContactManager::contactSigningKeyUuidExists($contact, $signatureUuid))
                {
                    return $rpcRequest->produceResponse(false);
                }

                ContactManager::removeContactSigningKey($contact, $signatureUuid);
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to remove contact signature', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            // Return success
            return $rpcRequest->produceResponse(true);
        }
    }