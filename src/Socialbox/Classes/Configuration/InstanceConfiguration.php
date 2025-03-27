<?php

    namespace Socialbox\Classes\Configuration;

    class InstanceConfiguration
    {
        private bool $enabled;
        private string $name;
        private ?string $domain;
        private ?string $rpcEndpoint;
        private array $dnsMocks;

        /**
         * Constructor that initializes object properties with the provided data.
         *
         * @param array $data An associative array with keys 'enabled', 'domain', 'private_key', and 'public_key'.
         * @return void
         */
        public function __construct(array $data)
        {
            $this->enabled = (bool)$data['enabled'];
            $this->name = $data['name'];
            $this->domain = $data['domain'];
            $this->rpcEndpoint = $data['rpc_endpoint'];
            $this->dnsMocks = $data['dns_mocks'];
        }

        /**
         * Checks if the current object is enabled.
         *
         * @return bool True if the object is enabled, false otherwise.
         */
        public function isEnabled(): bool
        {
            return $this->enabled;
        }

        public function getName(): string
        {
            return $this->name;
        }

        /**
         * Retrieves the domain.
         *
         * @return string|null The domain.
         */
        public function getDomain(): ?string
        {
            return strtolower($this->domain);
        }

        /**
         * @return string|null
         */
        public function getRpcEndpoint(): ?string
        {
            return $this->rpcEndpoint;
        }

        /**
         * @return array
         */
        public function getDnsMocks(): array
        {
            return $this->dnsMocks;
        }
    }