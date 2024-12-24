<?php

    namespace Socialbox\Classes\StandardMethods;

    use Exception;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Utilities;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\RegisteredPeerManager;
    use Socialbox\Managers\SessionManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class SettingsSetDisplayPicture extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(!$rpcRequest->containsParameter('image'))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Missing 'image' parameter");
            }

            if(strlen($rpcRequest->getParameter('image')) > Configuration::getStorageConfiguration()->getUserDisplayImagesMaxSize())
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Image size exceeds the maximum allowed size of " . Configuration::getStorageConfiguration()->getUserDisplayImagesMaxSize() . " bytes");
            }

            try
            {
                $decodedImage = base64_decode($rpcRequest->getParameter('image'));

                if($decodedImage === false)
                {
                    return $rpcRequest->produceError(StandardError::BAD_REQUEST, "Failed to decode JPEG image base64 data");
                }

                $sanitizedImage = Utilities::resizeImage(Utilities::sanitizeJpeg($decodedImage), 126, 126);
            }
            catch(Exception $e)
            {
                throw new StandardException('Failed to process JPEG image: ' . $e->getMessage(), StandardError::BAD_REQUEST, $e);
            }

            try
            {
                // Set the password
                RegisteredPeerManager::updateDisplayPicture($request->getPeer(), $sanitizedImage);

                // Remove the SET_DISPLAY_PICTURE flag
                SessionManager::removeFlags($request->getSessionUuid(), [SessionFlags::SET_DISPLAY_PICTURE]);

                // Check & update the session flow
                SessionManager::updateFlow($request->getSession());
            }
            catch(Exception $e)
            {
                throw new StandardException('Failed to update display picture: ' . $e->getMessage(), StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse(true);
        }
    }