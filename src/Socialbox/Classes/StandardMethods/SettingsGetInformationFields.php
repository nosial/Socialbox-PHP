<?php

    namespace Socialbox\Classes\StandardMethods;

    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\PeerInformationManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class SettingsGetInformationFields extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            try
            {
                $fieldRecords = PeerInformationManager::getFields($request->getPeer());
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardException('Failed to retrieve existing information fields', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse(array_map(fn($result) => $result->toInformationFieldState(), $fieldRecords));
        }
    }