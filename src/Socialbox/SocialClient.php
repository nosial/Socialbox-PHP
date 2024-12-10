<?php

    namespace Socialbox;

    use Socialbox\Classes\RpcClient;
    use Socialbox\Classes\Utilities;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\ResolutionException;
    use Socialbox\Exceptions\RpcException;
    use Socialbox\Objects\KeyPair;
    use Socialbox\Objects\RpcError;
    use Socialbox\Objects\RpcRequest;

    class SocialClient extends RpcClient
    {
        /**
         * Constructs a new instance with the specified domain.
         *
         * @param string $domain The domain to be set for the instance.
         * @throws ResolutionException
         */
        public function __construct(string $domain)
        {
            parent::__construct($domain);
        }

        /**
         * Creates a new session using the provided key pair.
         *
         * @param KeyPair $keyPair The key pair to be used for creating the session.
         * @return string The UUID of the created session.
         * @throws CryptographyException if there is an error in the cryptographic operations.
         * @throws RpcException if there is an error in the RPC request or if no response is received.
         */
        public function createSession(KeyPair $keyPair): string
        {
            $response = $this->sendRequest(new RpcRequest('createSession', Utilities::randomCrc32(), [
                'public_key' => $keyPair->getPublicKey()
            ]));

            if($response === null)
            {
                throw new RpcException('Failed to create the session, no response received');
            }

            if($response instanceof RpcError)
            {
                throw RpcException::fromRpcError($response);
            }

            $this->setSessionUuid($response->getResult());
            $this->setPrivateKey($keyPair->getPrivateKey());

            return $response->getResult();
        }

    }