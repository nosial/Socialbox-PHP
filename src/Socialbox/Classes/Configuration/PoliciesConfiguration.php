<?php

    namespace Socialbox\Classes\Configuration;

    class PoliciesConfiguration
    {
        private int $maxSigningKeys;
        private int $sessionInactivityExpires;

        public function __construct(array $data)
        {
            $this->maxSigningKeys = $data['max_signing_keys'];
            $this->sessionInactivityExpires = $data['session_inactivity_expires'];
        }

        /**
         * @return int
         */
        public function getMaxSigningKeys(): int
        {
            return $this->maxSigningKeys;
        }

        /**
         * @return int
         */
        public function getSessionInactivityExpires(): int
        {
            return $this->sessionInactivityExpires;
        }
    }