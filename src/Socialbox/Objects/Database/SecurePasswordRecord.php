<?php

    namespace Socialbox\Objects\Database;

    use DateTime;

    class SecurePasswordRecord
    {
        private string $peerUuid;
        private string $iv;
        private string $encryptedPassword;
        private string $encryptedTag;
        private DateTime $updated;

        /**
         * Constructor to initialize the object with provided data.
         *
         * @param array $data An associative array containing keys:
         *                    - 'peer_uuid': The UUID of the peer.
         *                    - 'iv': The initialization vector.
         *                    - 'encrypted_password': The encrypted password.
         *                    - 'encrypted_tag': The encrypted tag.
         *
         * @throws \DateMalformedStringException
         */
        public function __construct(array $data)
        {
            $this->peerUuid = $data['peer_uuid'];
            $this->iv = $data['iv'];
            $this->encryptedPassword = $data['encrypted_password'];
            $this->encryptedTag = $data['encrypted_tag'];
            $this->updated = new DateTime($data['updated']);
        }

        /**
         * Retrieves the UUID of the peer.
         *
         * @return string The UUID of the peer.
         */
        public function getPeerUuid(): string
        {
            return $this->peerUuid;
        }

        /**
         * Retrieves the initialization vector (IV) value.
         *
         * @return string The initialization vector.
         */
        public function getIv(): string
        {
            return $this->iv;
        }

        /**
         * Retrieves the encrypted password.
         *
         * @return string The encrypted password.
         */
        public function getEncryptedPassword(): string
        {
            return $this->encryptedPassword;
        }

        /**
         * Retrieves the encrypted tag.
         *
         * @return string The encrypted tag.
         */
        public function getEncryptedTag(): string
        {
            return $this->encryptedTag;
        }

        /**
         * Retrieves the updated timestamp.
         *
         * @return DateTime The updated timestamp.
         */
        public function getUpdated(): DateTime
        {
            return $this->updated;
        }

        public function toArray(): array
        {
            return [
                'peer_uuid' => $this->peerUuid,
                'iv' => $this->iv,
                'encrypted_password' => $this->encryptedPassword,
                'encrypted_tag' => $this->encryptedTag,
                'updated' => $this->updated->format('Y-m-d H:i:s')
            ];
        }

        public static function fromArray(array $data): SecurePasswordRecord
        {
            return new SecurePasswordRecord($data);
        }
    }