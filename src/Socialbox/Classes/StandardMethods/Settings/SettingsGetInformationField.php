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

    class SettingsGetInformationField extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(!$rpcRequest->containsParameter('field'))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'The required field parameter is missing');
            }
            $fieldName = InformationFieldName::tryFrom(strtoupper($rpcRequest->getParameter('field')));
            if($fieldName === null)
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'The provided field parameter is invalid');
            }

            try
            {
                $fieldRecord = PeerInformationManager::getField($request->getPeer(), $fieldName);
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to retrieve existing information fields', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            if($fieldRecord === null)
            {
                return $rpcRequest->produceError(StandardError::NOT_FOUND, 'The requested field does not exist');
            }

            return $rpcRequest->produceResponse($fieldRecord->toInformationFieldState());
        }
    }