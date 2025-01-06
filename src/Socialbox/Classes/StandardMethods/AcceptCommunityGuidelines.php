<?php

    namespace Socialbox\Classes\StandardMethods;

    use Socialbox\Abstracts\Method;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\SessionManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class AcceptCommunityGuidelines extends Method
    {

        /**
         * Executes the process of accepting the community guidelines.
         *
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(!$request->getSession()->flagExists(SessionFlags::VER_COMMUNITY_GUIDELINES))
            {
                return $rpcRequest->produceError(StandardError::FORBIDDEN, 'Community guidelines has already been accepted');
            }

            try
            {
                // Check & update the session flow
                SessionManager::updateFlow($request->getSession(), [SessionFlags::VER_COMMUNITY_GUIDELINES]);
            }
            catch (DatabaseOperationException $e)
            {
                return $rpcRequest->produceError(StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse(true);
        }
    }