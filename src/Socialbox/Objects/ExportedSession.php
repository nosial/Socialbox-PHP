<?php

    namespace Socialbox\Objects;

    use Socialbox\Interfaces\SerializableInterface;

    /**
     * Represents an exported session containing cryptographic keys, identifiers, and endpoints.
     */
    class ExportedSession implements SerializableInterface
    {
        private string $peerAddress;
        private string $rpcEndpoint;
        private string $remoteServer;
        private string $sessionUUID;
        private string $transportEncryptionAlgorithm;
        private int $serverKeypairExpires;
        private string $serverPublicSigningKey;
        private string $serverPublicEncryptionKey;
        private string $clientPublicSigningKey;
        private string $clientPrivateSigningKey;
        private string $clientPublicEncryptionKey;
        private string $clientPrivateEncryptionKey;
        private string $privateSharedSecret;
        private string $clientTransportEncryptionKey;
        private string $serverTransportEncryptionKey;

        /**
         * Constructor method to initialize class properties from the provided data array.
         *
         * @param array $data Associative array containing the required properties such as:
         *                    'peer_address', 'rpc_endpoint', 'session_uuid',
         *                    'server_public_signing_key', 'server_public_encryption_key',
         *                    'client_public_signing_key', 'client_private_signing_key',
         *                    'client_public_encryption_key', 'client_private_encryption_key',
         *                    'private_shared_secret', 'client_transport_encryption_key',
         *                    'server_transport_encryption_key'.
         *
         * @return void
         */
        public function __construct(array $data)
        {
            $this->peerAddress = $data['peer_address'];
            $this->rpcEndpoint = $data['rpc_endpoint'];
            $this->remoteServer = $data['remote_server'];
            $this->sessionUUID = $data['session_uuid'];
            $this->transportEncryptionAlgorithm = $data['transport_encryption_algorithm'];
            $this->serverKeypairExpires = $data['server_keypair_expires'];
            $this->serverPublicSigningKey = $data['server_public_signing_key'];
            $this->serverPublicEncryptionKey = $data['server_public_encryption_key'];
            $this->clientPublicSigningKey = $data['client_public_signing_key'];
            $this->clientPrivateSigningKey = $data['client_private_signing_key'];
            $this->clientPublicEncryptionKey = $data['client_public_encryption_key'];
            $this->clientPrivateEncryptionKey = $data['client_private_encryption_key'];
            $this->privateSharedSecret = $data['private_shared_secret'];
            $this->clientTransportEncryptionKey = $data['client_transport_encryption_key'];
            $this->serverTransportEncryptionKey = $data['server_transport_encryption_key'];
        }

        /**
         * Retrieves the peer address associated with the current instance.
         *
         * @return string The peer address.
         */
        public function getPeerAddress(): string
        {
            return $this->peerAddress;
        }

        /**
         * Retrieves the RPC endpoint.
         *
         * @return string The RPC endpoint.
         */
        public function getRpcEndpoint(): string
        {
            return $this->rpcEndpoint;
        }

        /**
         * Retrieves the remote server.
         *
         * @return string The remote server.
         */
        public function getRemoteServer(): string
        {
            return $this->remoteServer;
        }

        /**
         * Retrieves the session UUID associated with the current instance.
         *
         * @return string The session UUID.
         */
        public function getSessionUUID(): string
        {
            return $this->sessionUUID;
        }

        /**
         * Retrieves the transport encryption algorithm being used.
         *
         * @return string The transport encryption algorithm.
         */
        public function getTransportEncryptionAlgorithm(): string
        {
            return $this->transportEncryptionAlgorithm;
        }

        /**
         * Retrieves the expiration time of the server key pair.
         *
         * @return int The expiration timestamp of the server key pair.
         */
        public function getServerKeypairExpires(): int
        {
            return $this->serverKeypairExpires;
        }

        /**
         * Retrieves the public signing key of the server.
         *
         * @return string The server's public signing key.
         */
        public function getServerPublicSigningKey(): string
        {
            return $this->serverPublicSigningKey;
        }

        /**
         * Retrieves the server's public encryption key.
         *
         * @return string The server's public encryption key.
         */
        public function getServerPublicEncryptionKey(): string
        {
            return $this->serverPublicEncryptionKey;
        }

        /**
         * Retrieves the client's public signing key.
         *
         * @return string The client's public signing key.
         */
        public function getClientPublicSigningKey(): string
        {
            return $this->clientPublicSigningKey;
        }

        /**
         * Retrieves the client's private signing key.
         *
         * @return string The client's private signing key.
         */
        public function getClientPrivateSigningKey(): string
        {
            return $this->clientPrivateSigningKey;
        }

        /**
         * Retrieves the public encryption key of the client.
         *
         * @return string The client's public encryption key.
         */
        public function getClientPublicEncryptionKey(): string
        {
            return $this->clientPublicEncryptionKey;
        }

        /**
         * Retrieves the client's private encryption key.
         *
         * @return string The client's private encryption key.
         */
        public function getClientPrivateEncryptionKey(): string
        {
            return $this->clientPrivateEncryptionKey;
        }

        /**
         * Retrieves the private shared secret associated with the current instance.
         *
         * @return string The private shared secret.
         */
        public function getPrivateSharedSecret(): string
        {
            return $this->privateSharedSecret;
        }

        /**
         * Retrieves the client transport encryption key.
         *
         * @return string The client transport encryption key.
         */
        public function getClientTransportEncryptionKey(): string
        {
            return $this->clientTransportEncryptionKey;
        }

        /**
         * Retrieves the server transport encryption key associated with the current instance.
         *
         * @return string The server transport encryption key.
         */
        public function getServerTransportEncryptionKey(): string
        {
            return $this->serverTransportEncryptionKey;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'peer_address' => $this->peerAddress,
                'rpc_endpoint' => $this->rpcEndpoint,
                'remote_server' => $this->remoteServer,
                'session_uuid' => $this->sessionUUID,
                'transport_encryption_algorithm' => $this->transportEncryptionAlgorithm,
                'server_keypair_expires' => $this->serverKeypairExpires,
                'server_public_signing_key' => $this->serverPublicSigningKey,
                'server_public_encryption_key' => $this->serverPublicEncryptionKey,
                'client_public_signing_key' => $this->clientPublicSigningKey,
                'client_private_signing_key' => $this->clientPrivateSigningKey,
                'client_public_encryption_key' => $this->clientPublicEncryptionKey,
                'client_private_encryption_key' => $this->clientPrivateEncryptionKey,
                'private_shared_secret' => $this->privateSharedSecret,
                'client_transport_encryption_key' => $this->clientTransportEncryptionKey,
                'server_transport_encryption_key' => $this->serverTransportEncryptionKey,
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): ExportedSession
        {
            return new ExportedSession($data);
        }
    }