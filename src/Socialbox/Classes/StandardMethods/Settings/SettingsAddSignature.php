<?php

    namespace Socialbox\Classes\StandardMethods\Settings;

    use Exception;
    use InvalidArgumentException;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\Standard\InvalidRpcArgumentException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\SigningKeysManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class SettingsAddSignature extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(!$rpcRequest->containsParameter('public_key'))
            {
                throw new MissingRpcArgumentException('public_key');
            }

            $expires = null;
            if($rpcRequest->containsParameter('expires') && $rpcRequest->getParameter('expires') !== null)
            {
                $expires = (int)$rpcRequest->getParameter('expires');
            }
            if(!$rpcRequest->containsParameter('name'))
            {
                throw new MissingRpcArgumentException('name');
            }

            $name = null;
            if($rpcRequest->containsParameter('name') && $rpcRequest->getParameter('name') !== null)
            {
                $name = $rpcRequest->getParameter('name');
            }

            try
            {
                $peerUuid = $request->getPeer()->getUuid();
                if(SigningKeysManager::getSigningKeyCount($peerUuid) >= Configuration::getPoliciesConfiguration()->getMaxSigningKeys())
                {
                    return $rpcRequest->produceError(StandardError::FORBIDDEN, 'The maximum number of signing keys has been reached, the server\'s configured limit is: ' . Configuration::getPoliciesConfiguration()->getMaxSigningKeys());
                }

                $uuid = SigningKeysManager::addSigningKey($peerUuid, $rpcRequest->getParameter('public_key'), $name, $expires);
            }
            catch(InvalidArgumentException $e)
            {
                throw new InvalidRpcArgumentException($e);
            }
            catch(DatabaseOperationException $e)
            {
                throw new StandardRpcException('Failed to add the signing key', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse($uuid);
        }
    }