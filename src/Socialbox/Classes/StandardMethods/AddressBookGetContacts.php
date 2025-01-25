<?php

    namespace Socialbox\Classes\StandardMethods;

    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\ContactManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class AddressBookGetContacts extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            $limit = Configuration::getPoliciesConfiguration()->getGetContactsLimit();
            if($rpcRequest->containsParameter('limit'))
            {
                $limit = (int)$rpcRequest->getParameter('limit');
                if($limit < 1)
                {
                    return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'Invalid limit, must be greater than 0');
                }

                $limit = min($limit, Configuration::getPoliciesConfiguration()->getGetContactsLimit());
            }

            $page = 0;
            if($rpcRequest->containsParameter('page'))
            {
                $page = (int)$rpcRequest->getParameter('page');
                if($page < 0)
                {
                    return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'Invalid page, must be greater than or equal to 0');
                }

                $page = max($page, 0);
            }

            try
            {
                $contacts = ContactManager::getContacts($request->getPeer(), $limit, $page);
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardException('Failed to get contacts', StandardError::INTERNAL_SERVER_ERROR, $e);
            }


            return $rpcRequest->produceResponse(array_map(function($contact) {return $contact->toStandard();}, $contacts));
        }
    }