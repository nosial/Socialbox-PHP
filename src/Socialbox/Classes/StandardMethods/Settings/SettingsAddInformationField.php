<?php

    namespace Socialbox\Classes\StandardMethods\Settings;

    use Exception;
    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\PrivacyState;
    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\Types\InformationFieldName;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\PeerInformationManager;
    use Socialbox\Managers\SessionManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class SettingsAddInformationField extends Method
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

            // Privacy parameter is optional
            $privacy = null;
            if($rpcRequest->containsParameter('privacy') && $rpcRequest->getParameter('privacy') !== null)
            {
                $privacy = PrivacyState::tryFrom(strtoupper($rpcRequest->getParameter('privacy')));
                if($privacy === null)
                {
                    return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'The provided privacy parameter is invalid');
                }
            }

            try
            {
                $peer = $request->getPeer();
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardException('Failed to retrieve current peer information', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            try
            {
                if (PeerInformationManager::fieldExists($peer, $fieldName))
                {
                    return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, 'The provided field parameter is already registered, use settingsUpdateInformationField or settingsUpdateInformationPrivacy instead');
                }

                PeerInformationManager::addField($peer, $fieldName, $value, $privacy);
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardException('Failed to add the information field', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            // Update the session flow if necessary
            try
            {
                switch($fieldName)
                {
                    case InformationFieldName::DISPLAY_NAME:
                        SessionManager::updateFlow($request->getSession(), [SessionFlags::SET_DISPLAY_NAME]);
                        break;

                    case InformationFieldName::FIRST_NAME:
                        SessionManager::updateFlow($request->getSession(), [SessionFlags::SET_FIRST_NAME]);
                        break;

                    case InformationFieldName::MIDDLE_NAME:
                        SessionManager::updateFlow($request->getSession(), [SessionFlags::SET_MIDDLE_NAME]);
                        break;

                    case InformationFieldName::LAST_NAME:
                        SessionManager::updateFlow($request->getSession(), [SessionFlags::SET_LAST_NAME]);
                        break;

                    case InformationFieldName::BIRTHDAY:
                        SessionManager::updateFlow($request->getSession(), [SessionFlags::SET_BIRTHDAY]);
                        break;

                    case InformationFieldName::PHONE_NUMBER:
                        SessionManager::updateFlow($request->getSession(), [SessionFlags::SET_PHONE]);
                        break;

                    case InformationFieldName::EMAIL_ADDRESS:
                        SessionManager::updateFlow($request->getSession(), [SessionFlags::SET_EMAIL]);
                        break;

                    case InformationFieldName::URL:
                        SessionManager::updateFlow($request->getSession(), [SessionFlags::SET_URL]);
                        break;

                    default:
                        break;
                }
            }
            catch (Exception $e)
            {
                try
                {
                    // Rollback the information field otherwise the peer will be stuck with an incomplete session flow
                    PeerInformationManager::deleteField($peer, $fieldName);
                }
                catch (DatabaseOperationException $e)
                {
                    // Something is seriously wrong if we can't roll back the information field
                    throw new StandardException('Failed to rollback the information field', StandardError::INTERNAL_SERVER_ERROR, $e);
                }

                if($e instanceof StandardException)
                {
                    throw $e;
                }

                throw new StandardException('Failed to update the session flow', StandardError::INTERNAL_SERVER_ERROR, $e);
            }


            return $rpcRequest->produceResponse(true);
        }
    }