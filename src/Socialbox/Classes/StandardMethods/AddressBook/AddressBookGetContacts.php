<?php

    namespace Socialbox\Classes\StandardMethods\AddressBook;

    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\InvalidRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\ContactManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class AddressBookGetContacts extends Method
    {
        /**
         * Returns the contacts in the address book.
         *
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            $limit = Configuration::getPoliciesConfiguration()->getGetContactsLimit();
            if($rpcRequest->containsParameter('limit', true))
            {
                $limit = (int)$rpcRequest->getParameter('limit');
                if($limit <= 0)
                {
                    throw new InvalidRpcArgumentException('limit', 'Invalid limit, must be greater than 0');
                }

                $limit = min($limit, Configuration::getPoliciesConfiguration()->getGetContactsLimit());
            }

            $page = 0;
            if($rpcRequest->containsParameter('page', true))
            {
                $page = (int)$rpcRequest->getParameter('page');
                if($page < 0)
                {
                    throw new InvalidRpcArgumentException('page', 'Invalid page, must be greater than or equal to 0');
                }

                $page = max($page, 0);
            }

            try
            {
                return $rpcRequest->produceResponse(ContactManager::getStandardContacts($request->getPeer(), $limit, $page));
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to get contacts', StandardError::INTERNAL_SERVER_ERROR, $e);
            }
        }
    }