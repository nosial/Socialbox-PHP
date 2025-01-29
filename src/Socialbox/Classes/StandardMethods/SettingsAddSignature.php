<?php

    namespace Socialbox\Classes\StandardMethods;

    use Exception;
    use InvalidArgumentException;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Configuration;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\StandardException;
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
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Missing 'public_key' parameter");
            }

            $expires = null;
            if($rpcRequest->containsParameter('expires') && $rpcRequest->getParameter('expires') !== null)
            {
                $expires = (int)$rpcRequest->getParameter('expires');
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

                $uuid = SigningKeysManager::addSigningKey($peerUuid, $rpcRequest->getParameter('public_key'), $expires, $name);
            }
            catch(InvalidArgumentException $e)
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, $e->getMessage());
            }
            catch(Exception $e)
            {
                throw new StandardException('Failed to add the signing key', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse($uuid);
        }
    }