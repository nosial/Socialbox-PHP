<?php

    namespace Socialbox\Classes\StandardMethods;

    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Resources;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;
    use Socialbox\Objects\Standard\ServerDocument;

    class GetTermsOfService extends Method
    {

        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            return $rpcRequest->produceResponse(new ServerDocument([
                'last_updated' => Configuration::getRegistrationConfiguration()->getTermsOfServiceDate(),
                'title' => 'Terms of Service',
                'content' => Resources::getTermsOfService()
            ]));
        }
    }