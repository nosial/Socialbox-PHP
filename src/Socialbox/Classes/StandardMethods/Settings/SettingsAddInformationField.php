<?php

    namespace Socialbox\Classes\StandardMethods\Settings;

    use Exception;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Logger;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\PrivacyState;
    use Socialbox\Enums\StandardError;
    use Socialbox\Enums\Types\InformationFieldName;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\InvalidRpcArgumentException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\PeerInformationManager;
    use Socialbox\Managers\SessionManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class SettingsAddInformationField extends Method
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

            $fieldName = InformationFieldName::tryFrom(strtoupper((string)$rpcRequest->getParameter('field')));
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

            // Privacy parameter is optional
            $privacy = null;
            if($rpcRequest->containsParameter('privacy') && $rpcRequest->getParameter('privacy') !== null)
            {
                $privacy = PrivacyState::tryFrom(strtoupper((string)$rpcRequest->getParameter('privacy')));
                if($privacy === null)
                {
                    throw new InvalidRpcArgumentException('privacy');
                }
            }

            try
            {
                $peer = $request->getPeer();
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to retrieve peer information', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            try
            {
                if (PeerInformationManager::fieldExists($peer, $fieldName))
                {
                    // Return False, because the field already exists
                    return $rpcRequest->produceResponse(false);
                }

                PeerInformationManager::addField($peer, $fieldName, $value, $privacy);
            }
            catch (DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to add the information field', StandardError::INTERNAL_SERVER_ERROR, $e);
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
                    Logger::getLogger()->critical('Failed to rollback information field', $e);
                }

                if($e instanceof StandardRpcException)
                {
                    throw $e;
                }

                throw new StandardRpcException('Failed to update the session flow', StandardError::INTERNAL_SERVER_ERROR, $e);
            }


            return $rpcRequest->produceResponse(true);
        }
    }