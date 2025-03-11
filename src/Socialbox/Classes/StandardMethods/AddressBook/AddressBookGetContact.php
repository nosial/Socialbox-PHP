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

    class AddressBookGetContact extends Method
    {
        /**
         * Returns the contact information for the given peer address.
         *
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(!$rpcRequest->containsParameter('peer'))
            {
                throw new MissingRpcArgumentException('peer');
            }

            $address = PeerAddress::fromAddress($rpcRequest->getParameter('peer'));

            try
            {
                if(!ContactManager::isContact($request->getPeer(), $address))
                {
                    return $rpcRequest->produceResponse();
                }

                return $rpcRequest->produceResponse(ContactManager::getStandardContact($request->getPeer(), $address));
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to get contact', StandardError::INTERNAL_SERVER_ERROR, $e);
            }
        }
    }