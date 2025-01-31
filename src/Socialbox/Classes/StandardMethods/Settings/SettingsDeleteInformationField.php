<?php

    namespace Socialbox\Classes\StandardMethods\Settings;

    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
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

    class SettingsDeleteInformationField extends Method
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
            $fieldName = InformationFieldName::tryFrom(strtoupper($rpcRequest->getParameter('field')));
            if($fieldName === null)
            {
                throw new InvalidRpcArgumentException('field');
            }

            try
            {
                if(!PeerInformationManager::fieldExists($request->getPeer(), $fieldName))
                {
                    return $rpcRequest->produceError(StandardError::FORBIDDEN, 'The information field does not exist');
                }
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to check if the information field exists', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            switch($fieldName)
            {
                case InformationFieldName::DISPLAY_NAME:
                    if(Configuration::getRegistrationConfiguration()->isDisplayNameRequired())
                    {
                        return $rpcRequest->produceError(StandardError::FORBIDDEN, 'A display name is required for this server');
                    }
                    break;

                case InformationFieldName::FIRST_NAME:
                    if(Configuration::getRegistrationConfiguration()->isFirstNameRequired())
                    {
                        return $rpcRequest->produceError(StandardError::FORBIDDEN, 'A first name is required for this server');
                    }
                    break;

                case InformationFieldName::MIDDLE_NAME:
                    if(Configuration::getRegistrationConfiguration()->isMiddleNameRequired())
                    {
                        return $rpcRequest->produceError(StandardError::FORBIDDEN, 'A middle name is required for this server');
                    }
                    break;

                case InformationFieldName::LAST_NAME:
                    if(Configuration::getRegistrationConfiguration()->isLastNameRequired())
                    {
                        return $rpcRequest->produceError(StandardError::FORBIDDEN, 'A last name is required for this server');
                    }
                    break;

                case InformationFieldName::BIRTHDAY:
                    if(Configuration::getRegistrationConfiguration()->isBirthdayRequired())
                    {
                        return $rpcRequest->produceError(StandardError::FORBIDDEN, 'A birthday is required for this server');
                    }
                    break;

                case InformationFieldName::PHONE_NUMBER:
                    if(Configuration::getRegistrationConfiguration()->isPhoneNumberRequired())
                    {
                        return $rpcRequest->produceError(StandardError::FORBIDDEN, 'A phone number is required for this server');
                    }
                    break;

                case InformationFieldName::EMAIL_ADDRESS:
                    if(Configuration::getRegistrationConfiguration()->isEmailAddressRequired())
                    {
                        return $rpcRequest->produceError(StandardError::FORBIDDEN, 'An email address is required for this server');
                    }
                    break;

                case InformationFieldName::URL:
                    if(Configuration::getRegistrationConfiguration()->isUrlRequired())
                    {
                        return $rpcRequest->produceError(StandardError::FORBIDDEN, 'A URL is required for this server');
                    }
                    break;

                default:
                    break;
            }

            try
            {
                PeerInformationManager::deleteField($request->getPeer(), $fieldName);
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to delete the information field', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse(true);
        }
    }