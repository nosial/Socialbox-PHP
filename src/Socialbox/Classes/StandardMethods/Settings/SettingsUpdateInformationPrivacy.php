<?php

    namespace Socialbox\Classes\StandardMethods\Settings;

    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\PrivacyState;
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
                throw new MissingRpcArgumentException('field');
            }
            $fieldName = InformationFieldName::tryFrom(strtoupper((string)$rpcRequest->getParameter('field')));
            if($fieldName === null)
            {
                throw new InvalidRpcArgumentException('field');
            }

            // Privacy parameter is required
            if(!$rpcRequest->containsParameter('privacy'))
            {
                throw new MissingRpcArgumentException('privacy');
            }

            $privacy = PrivacyState::tryFrom(strtoupper((string)$rpcRequest->getParameter('privacy')));
            if($privacy === null)
            {
                throw new InvalidRpcArgumentException('privacy');
            }

            try
            {
                $requestingPeer = $request->getPeer();
                if(!PeerInformationManager::fieldExists($requestingPeer, $fieldName))
                {
                    return $rpcRequest->produceResponse(false);
                }

                PeerInformationManager::updatePrivacyState($requestingPeer, $fieldName, $privacy);
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to update the information field', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse(true);
        }
    }