<?php

    namespace Socialbox\Objects\Database;

    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\SecuredPassword;
    use Socialbox\Exceptions\CryptographyException;

    class EncryptionRecord
    {
        private string $data;
        private string $iv;
        private string $tag;

        /**
         * Public constructor for the EncryptionRecord
         *
         * @param array $data
         */
        public function __construct(array $data)
        {
            $this->data = $data['data'];
            $this->iv = $data['iv'];
            $this->tag = $data['tag'];
        }

        /**
         * Retrieves the stored data.
         *
         * @return string The stored data.
         */
        public function getData(): string
        {
            return $this->data;
        }

        /**
         * Retrieves the initialization vector (IV).
         *
         * @return string The initialization vector.
         */
        public function getIv(): string
        {
            return $this->iv;
        }

        /**
         * Retrieves the tag.
         *
         * @return string The tag.
         */
        public function getTag(): string
        {
            return $this->tag;
        }

        /**
         * Decrypts the encrypted record using available encryption keys.
         *
         * Iterates through the configured encryption keys to attempt decryption of the data.
         * If successful, returns a DecryptedRecord object with the decrypted data.
         * Throws an exception if decryption fails with all available keys.
         *
         * @return DecryptedRecord The decrypted record containing the original data.
         * @throws CryptographyException If decryption fails with all provided keys.
         */
        public function decrypt(): DecryptedRecord
        {
            foreach(Configuration::getInstanceConfiguration()->getEncryptionKeys() as $encryptionKey)
            {
                $decryptedVault = openssl_decrypt(base64_decode($this->data), SecuredPassword::ENCRYPTION_ALGORITHM,
                    $encryptionKey, OPENSSL_RAW_DATA, base64_decode($this->iv), base64_decode($this->tag)
                );

                if ($decryptedVault !== false)
                {
                    return new DecryptedRecord(json_decode($decryptedVault, true));
                }
            }

            throw new CryptographyException("Decryption failed");
        }
    }