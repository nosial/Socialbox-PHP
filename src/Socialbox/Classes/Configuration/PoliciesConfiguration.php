<?php

    namespace Socialbox\Classes\Configuration;

    class PoliciesConfiguration
    {
        private int $maxSigningKeys;

        public function __construct(array $data)
        {
            $this->maxSigningKeys = $data['max_signing_keys'];
        }

        /**
         * @return int
         */
        public function getMaxSigningKeys(): int
        {
            return $this->maxSigningKeys;
        }
    }