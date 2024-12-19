<?php

    namespace Socialbox\Objects;

    /**
     * Represents an exported session containing cryptographic keys, identifiers, and endpoints.
     */
    class ExportedSession
    {
        private string $peerAddress;
        private string $privateKey;
        private string $publicKey;
        private string $encryptionKey;
        private string $serverPublicKey;
        private string $rpcEndpoint;
        private string $sessionUuid;

        /**
         * Initializes a new instance of the class with the provided data.
         *
         * @param array $data An associative array containing the configuration data.
         *                     Expected keys:
         *                     - 'peer_address': The address of the peer.
         *                     - 'private_key': The private key for secure communication.
         *                     - 'public_key': The public key for secure communication.
         *                     - 'encryption_key': The encryption key used for communication.
         *                     - 'server_public_key': The server's public key.
         *                     - 'rpc_endpoint': The RPC endpoint for network communication.
         *                     - 'session_uuid': The unique identifier for the session.
         *
         * @return void
         */
        public function __construct(array $data)
        {
            $this->peerAddress = $data['peer_address'];
            $this->privateKey = $data['private_key'];
            $this->publicKey = $data['public_key'];
            $this->encryptionKey = $data['encryption_key'];
            $this->serverPublicKey = $data['server_public_key'];
            $this->rpcEndpoint = $data['rpc_endpoint'];
            $this->sessionUuid = $data['session_uuid'];
        }

        /**
         * Retrieves the address of the peer.
         *
         * @return string The peer's address as a string.
         */
        public function getPeerAddress(): string
        {
            return $this->peerAddress;
        }

        /**
         * Retrieves the private key.
         *
         * @return string The private key.
         */
        public function getPrivateKey(): string
        {
            return $this->privateKey;
        }

        /**
         * Retrieves the public key.
         *
         * @return string The public key.
         */
        public function getPublicKey(): string
        {
            return $this->publicKey;
        }

        /**
         * Retrieves the encryption key.
         *
         * @return string The encryption key.
         */
        public function getEncryptionKey(): string
        {
            return $this->encryptionKey;
        }

        /**
         * Retrieves the public key of the server.
         *
         * @return string The server's public key.
         */
        public function getServerPublicKey(): string
        {
            return $this->serverPublicKey;
        }

        /**
         * Retrieves the RPC endpoint URL.
         *
         * @return string The RPC endpoint.
         */
        public function getRpcEndpoint(): string
        {
            return $this->rpcEndpoint;
        }

        /**
         * Retrieves the unique identifier for the current session.
         *
         * @return string The session UUID.
         */
        public function getSessionUuid(): string
        {
            return $this->sessionUuid;
        }

        /**
         * Converts the current instance into an array representation.
         *
         * @return array An associative array containing the instance properties and their respective values.
         */
        public function toArray(): array
        {
            return [
                'peer_address' => $this->peerAddress,
                'private_key' => $this->privateKey,
                'public_key' => $this->publicKey,
                'encryption_key' => $this->encryptionKey,
                'server_public_key' => $this->serverPublicKey,
                'rpc_endpoint' => $this->rpcEndpoint,
                'session_uuid' => $this->sessionUuid
            ];
        }

        /**
         * Creates an instance of ExportedSession from the provided array.
         *
         * @param array $data The input data used to construct the ExportedSession instance.
         * @return ExportedSession The new ExportedSession instance created from the given data.
         */
        public static function fromArray(array $data): ExportedSession
        {
            return new ExportedSession($data);
        }
    }