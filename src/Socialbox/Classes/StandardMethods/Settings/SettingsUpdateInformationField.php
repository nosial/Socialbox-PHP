<?php

    namespace Socialbox\Classes\StandardMethods\Settings;

    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\Types\InformationFieldName;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\InvalidRpcArgumentException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\PeerInformationManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class SettingsUpdateInformationField extends Method
    {
        /**
         * @inheritDoc
         * @noinspection DuplicatedCode
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            // Field parameter is required
            if(!$rpcRequest->containsParameter('field'))
            {
                throw new MissingRpcArgumentException('field');
            }
            $fieldName = InformationFieldName::tryFrom(strtoupper($rpcRequest->getParameter('field')));
            if($fieldName === null)
            {
                throw new InvalidRpcArgumentException('field');
            }

            // Value parameter is required
            if(!$rpcRequest->containsParameter('value'))
            {
                throw new MissingRpcArgumentException('value');
            }
            $value = $rpcRequest->getParameter('value');
            if(!$fieldName->validate($value))
            {
                throw new InvalidRpcArgumentException('value');
            }

            try
            {
                $peer = $request->getPeer();
                if(!PeerInformationManager::fieldExists($peer, $fieldName))
                {
                    return $rpcRequest->produceResponse(false);
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