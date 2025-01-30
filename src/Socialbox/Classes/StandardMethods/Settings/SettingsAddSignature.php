<?php

    namespace Socialbox\Classes\StandardMethods\Settings;

    use Exception;
    use InvalidArgumentException;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
    use Socialbox\Enums\StandardError;
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

            $peerUuid = $request->getPeer()->getUuid();

            try
            {
                if(SigningKeysManager::getSigningKeyCount($peerUuid) >= Configuration::getPoliciesConfiguration()->getMaxSigningKeys())
                {
                    return $rpcRequest->produceError(StandardError::FORBIDDEN, 'The maximum number of signing keys has been reached');
                }

                $uuid = SigningKeysManager::addSigningKey($peerUuid, $rpcRequest->getParameter('public_key'), $name, $expires);
            }
            catch(InvalidArgumentException $e)
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, $e->getMessage());
            }
            catch(Exception $e)
            {
                throw new StandardRpcException('Failed to add the signing key', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse($uuid);
        }
    }