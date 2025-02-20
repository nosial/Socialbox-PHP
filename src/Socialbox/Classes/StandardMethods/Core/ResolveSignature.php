<?php

    namespace Socialbox\Classes\StandardMethods\Core;

    use Exception;
    use InvalidArgumentException;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Validator;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\Standard\InvalidRpcArgumentException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\RpcRequest;
    use Socialbox\Socialbox;
    use Symfony\Component\Uid\Uuid;

    class ResolveSignature extends Method
    {

        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            // Check if the required 'peer' parameter is set.
            if(!$rpcRequest->containsParameter('peer'))
            {
                throw new MissingRpcArgumentException('peer');
            }

            if(!$rpcRequest->containsParameter('signature_uuid'))
            {
                throw new MissingRpcArgumentException('signature_uuid');
            }
            elseif(!Validator::validateUuid($rpcRequest->getParameter('signature_uuid')))
            {
                throw new InvalidRpcArgumentException('signature_uuid', 'Invalid UUID V4');
            }

            return $rpcRequest->produceResponse(Socialbox::resolvePeerSignature(
                $rpcRequest->getParameter('peer'), $rpcRequest->getParameter('signature_uuid')
            ));
        }
    }