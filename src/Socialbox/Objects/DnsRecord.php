<?php

    namespace Socialbox\Objects;

    class DnsRecord
    {
        private string $rpcEndpoint;
        private string $publicSigningKey;
        private int $expires;

        /**
         * Constructor for initializing the class with required parameters.
         *
         * @param string $rpcEndpoint The RPC endpoint.
         * @param string $publicSigningKey The public signing key.
         * @param int $expires The expiration time in seconds.
         * @return void
         */
        public function __construct(string $rpcEndpoint, string $publicSigningKey, int $expires)
        {
            $this->rpcEndpoint = $rpcEndpoint;
            $this->publicSigningKey = $publicSigningKey;
            $this->expires = $expires;
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
         * Retrieves the public signing key.
         *
         * @return string Returns the public signing key as a string.
         */
        public function getPublicSigningKey(): string
        {
            return $this->publicSigningKey;
        }

        /**
         * Retrieves the expiration time.
         *
         * @return int The expiration timestamp as an integer.
         */
        public function getExpires(): int
        {
            return $this->expires;
        }

        /**
         * Creates a new instance of DnsRecord from the provided array of data.
         *
         * @param array $data An associative array containing the keys 'rpc_endpoint', 'public_key', and 'expires'
         *                    required to instantiate a DnsRecord object.
         * @return DnsRecord Returns a new DnsRecord instance populated with the data from the array.
         */
        public static function fromArray(array $data): DnsRecord
        {
            return new DnsRecord($data['rpc_endpoint'], $data['public_key'], $data['expires']);
        }
    }