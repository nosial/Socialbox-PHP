<?php

    namespace Socialbox\Classes\StandardMethods;

    use InvalidArgumentException;
    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\ContactManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\RpcRequest;

    class AddressBookGetContact extends Method
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

            try
            {
                if(!ContactManager::isContact($request->getPeer(), $address))
                {
                    return $rpcRequest->produceError(StandardError::FORBIDDEN, 'Contact does not exist');
                }

                $contact = ContactManager::getContact($request->getPeer(), $address);
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardException('Failed to get contact', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse($contact->toStandard());
        }
    }