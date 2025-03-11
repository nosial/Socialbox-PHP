<?php

    namespace Socialbox\Classes\StandardMethods\AddressBook;

    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
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
            if($rpcRequest->containsParameter('limit'))
            {
                $limit = (int)$rpcRequest->getParameter('limit');
            }

            $page = 0;
            if($rpcRequest->containsParameter('page'))
            {
                $page = (int)$rpcRequest->getParameter('page');
            }

            try
            {
                return $rpcRequest->produceResponse(ContactManager::getStandardContacts($request->getPeer()->getUuid(), $limit, $page));
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to get contacts', StandardError::INTERNAL_SERVER_ERROR, $e);
            }
        }
    }