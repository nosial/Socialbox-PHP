<?php

    namespace Socialbox\Classes\StandardMethods;

    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\PrivacyState;
    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\Types\InformationFieldName;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\PeerInformationManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class SettingsUpdateInformationPrivacy extends Method
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

            // Privacy parameter is required
            $privacy = null;
            if(!$rpcRequest->containsParameter('privacy'))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'The required privacy parameter is missing');
            }
            $privacy = PrivacyState::tryFrom(strtoupper($rpcRequest->getParameter('privacy')));
            if($privacy === null)
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'The provided privacy parameter is invalid');
            }

            try
            {
                $peer = $request->getPeer();
                if(!PeerInformationManager::fieldExists($peer, $fieldName))
                {
                    return $rpcRequest->produceError(StandardError::FORBIDDEN, 'The information field does not exist');
                }

                PeerInformationManager::updatePrivacyState($peer, $fieldName, $privacy);
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardException('Failed to update the information field', StandardError::INTERNAL_SERVER_ERROR, $e);
            }


            return $rpcRequest->produceResponse(true);
        }
    }