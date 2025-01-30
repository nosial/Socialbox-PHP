<?php

    namespace Socialbox\Classes\StandardMethods\Settings;

    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\Types\InformationFieldName;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\PeerInformationManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class SettingsUpdateInformationField extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            // Field parameter is required
            if(!$rpcRequest->containsParameter('field'))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'The required field parameter is missing');
            }
            $fieldName = InformationFieldName::tryFrom(strtoupper($rpcRequest->getParameter('field')));
            if($fieldName === null)
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'The provided field parameter is invalid');
            }

            // Value parameter is required
            if(!$rpcRequest->containsParameter('value'))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'The required value parameter is missing');
            }
            $value = $rpcRequest->getParameter('value');
            if(!$fieldName->validate($value))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'The provided value parameter is invalid');
            }

            try
            {
                $peer = $request->getPeer();
                if(!PeerInformationManager::fieldExists($peer, $fieldName))
                {
                    return $rpcRequest->produceError(StandardError::FORBIDDEN, 'The information field does not exist');
                }

                PeerInformationManager::updateField($peer, $fieldName, $value);
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to update the information field', StandardError::INTERNAL_SERVER_ERROR, $e);
            }


            return $rpcRequest->produceResponse(true);
        }
    }