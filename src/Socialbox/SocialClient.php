<?php

    namespace Socialbox;

    use Socialbox\Classes\RpcClient;
    use Socialbox\Classes\Utilities;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Exceptions\ResolutionException;
    use Socialbox\Exceptions\RpcException;
    use Socialbox\Objects\ExportedSession;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\RpcRequest;
    use Socialbox\Objects\Standard\SessionState;

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

        /**
         * Retrieves the current state of the session from the server.
         *
         * @return SessionState Returns an instance of SessionState representing the session's current state.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function getSessionState(): SessionState
        {
            return SessionState::fromArray($this->sendRequest(
                new RpcRequest('getSessionState', Utilities::randomCrc32())
            )->getResponse()->getResult());
        }

        /**
         * Retrieves the privacy policy from the server.
         *
         * @return string Returns the privacy policy as a string.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function getPrivacyPolicy(): string
        {
            return $this->sendRequest(
                new RpcRequest('getPrivacyPolicy', Utilities::randomCrc32())
            )->getResponse()->getResult();
        }

        /**
         * Accepts the privacy policy by sending a request to the server.
         *
         * @return true Returns true if the privacy policy is successfully accepted.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function acceptPrivacyPolicy(): true
        {
            return (bool)$this->sendRequest(
                new RpcRequest('acceptPrivacyPolicy', Utilities::randomCrc32())
            )->getResponse()->getResult();
        }

        /**
         * Retrieves the terms of service from the server.
         *
         * @return string Returns the terms of service as a string.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function getTermsOfService(): string
        {
            return $this->sendRequest(new RpcRequest('getTermsOfService', Utilities::randomCrc32())
            )->getResponse()->getResult();
        }

        /**
         * Sends a request to accept the terms of service and verifies the response.
         *
         * @return true Returns true if the terms of service are successfully accepted.
         * @throws RpcException Thrown if the RPC request fails.
         */
        public function acceptTermsOfService(): true
        {
            return (bool)$this->sendRequest(
                new RpcRequest('acceptTermsOfService', Utilities::randomCrc32())
            )->getResponse()->getResult();
        }
    }