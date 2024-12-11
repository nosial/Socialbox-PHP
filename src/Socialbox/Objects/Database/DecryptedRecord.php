<?php

    namespace Socialbox\Objects\Database;

    class DecryptedRecord
    {
        private string $key;
        private string $pepper;
        private string $salt;

        public function __construct(array $data)
        {
            $this->key = $data['key'];
            $this->pepper = $data['pepper'];
            $this->salt = $data['salt'];
        }

        /**
         * @return string
         */
        public function getKey(): string
        {
            return $this->key;
        }

        /**
         * @return string
         */
        public function getPepper(): string
        {
            return $this->pepper;
        }

        /**
         * @return string
         */
        public function getSalt(): string
        {
            return $this->salt;
        }
    }