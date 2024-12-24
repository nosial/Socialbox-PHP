<?php

    namespace Socialbox\Classes\StandardMethods;

    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Resources;
    use Socialbox\Enums\StandardError;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;
    use Socialbox\Objects\Standard\ServerDocument;

    class GetCommunityGuidelines extends Method
    {

        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            return $rpcRequest->produceResponse(new ServerDocument([
                'last_updated' => Configuration::getRegistrationConfiguration()->getCommunityGuidelinesDate(),
                'title' => 'Community Guidelines',
                'content' => Resources::getCommunityGuidelines()
            ]));
        }
    }