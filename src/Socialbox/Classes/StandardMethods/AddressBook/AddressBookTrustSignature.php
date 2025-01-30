<?php

    namespace Socialbox\Classes\StandardMethods\AddressBook;

    use InvalidArgumentException;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
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
    use Socialbox\Socialbox;
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

            if(!$rpcRequest->containsParameter('uuid'))
            {
                throw new MissingRpcArgumentException('uuid');
            }

            try
            {
                $uuid = Uuid::fromString($rpcRequest->getParameter('uuid'));
            }
            catch(InvalidArgumentException $e)
            {
                throw new InvalidRpcArgumentException('uuid', $e->getMessage());
            }

            $signingKey = Socialbox::resolvePeerSignature($address, $uuid);

            try
            {
                // Check if the contact already exists
                $peer = $request->getPeer();
                if(!ContactManager::isContact($peer, $address))
                {
                    ContactManager::createContact($peer, $address);
                }

                $contact = ContactManager::getContact($peer, $address);

                if(ContactManager::contactGetSigningKeysCount($contact) > Configuration::getPoliciesConfiguration()->getMaxContactSigningKeys())
                {
                    return $rpcRequest->produceError(StandardError::FORBIDDEN, 'The contact has exceeded the maximum amount of trusted signatures');
                }
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to check contact state with calling peer', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            if($signingKey === null)
            {
                return $rpcRequest->produceError(StandardError::NOT_FOUND, 'The requested signature key was not found');
            }

            try
            {
                if(ContactManager::contactSigningKeyUuidExists($contact, $signingKey->getUuid()))
                {
                    return $rpcRequest->produceResponse(false);
                }

                if(ContactManager::contactSigningKeyExists($contact, $signingKey->getPublicKey()))
                {
                    return $rpcRequest->produceResponse(false);
                }

                ContactManager::addContactSigningKey($contact, $signingKey);
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to trust contact signature', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            // Return success
            return $rpcRequest->produceResponse(true);
        }
    }