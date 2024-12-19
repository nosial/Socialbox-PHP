<?php

    namespace Socialbox;

    use Socialbox\Classes\RpcClient;
    use Socialbox\Classes\Utilities;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\ResolutionException;
    use Socialbox\Exceptions\RpcException;
    use Socialbox\Objects\ExportedSession;
    use Socialbox\Objects\KeyPair;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\RpcError;
    use Socialbox\Objects\RpcRequest;

    class SocialClient extends RpcClient
    {
        /**
         * Constructs the object from an array of data.
         *
         * @param string|PeerAddress $peerAddress The address of the peer to connect to.
         * @param ExportedSession|null $exportedSession Optional. The exported session to use for communication.
         * @throws CryptographyException If the public key is invalid.
         * @throws ResolutionException If the domain cannot be resolved.
         * @throws RpcException If the RPC request fails.
         */
        public function __construct(string|PeerAddress $peerAddress, ?ExportedSession $exportedSession=null)
        {
            parent::__construct($peerAddress, $exportedSession);
        }

        /**
         * Sends a ping request to the server and checks the response.
         *
         * @return true Returns true if the ping request succeeds.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function ping(): true
        {
            return (bool)$this->sendRequest(
                new RpcRequest('ping', Utilities::randomCrc32())
            )->getResponse()->getResult();
        }

    }