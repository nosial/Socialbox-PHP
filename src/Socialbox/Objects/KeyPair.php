<?php

    namespace Socialbox\Objects;

    class KeyPair
    {
        private string $publicKey;
        private string $privateKey;

        /**
         * Constructor method for initializing the class with a public key and private key.
         *
         * @param string $publicKey The public key to be used.
         * @param string $privateKey The private key to be used.
         *
         * @return void
         */
        public function __construct(string $publicKey, string $privateKey)
        {
            $this->publicKey = $publicKey;
            $this->privateKey = $privateKey;
        }

        /**
         * Retrieves the public key associated with this instance.
         *
         * @return string The public key.
         */
        public function getPublicKey(): string
        {
            return $this->publicKey;
        }

        /**
         * Retrieves the private key associated with the instance.
         *
         * @return string The private key as a string.
         */
        public function getPrivateKey(): string
        {
            return $this->privateKey;
        }
    }