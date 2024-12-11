<?php

    namespace Socialbox\Classes;

    use DateTime;
    use Random\RandomException;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Objects\Database\EncryptionRecord;
    use Socialbox\Objects\Database\SecurePasswordRecord;

    class SecuredPassword
    {
        public const string ENCRYPTION_ALGORITHM = 'aes-256-gcm';
        public const int ITERATIONS = 500000; // Increased iterations for PBKDF2
        public const int KEY_LENGTH = 256; // Increased key length
        public const int PEPPER_LENGTH = 64;

        /**
         * Encrypts a password using a derived key and other cryptographic elements
         * to ensure secure storage.
         *
         * @param string $peerUuid The unique identifier of the peer associated with the password.
         * @param string $password The plain text password to be secured.
         * @param EncryptionRecord $record The encryption record containing information such as
         *                                  the key, salt, and pepper required for encryption.
         * @return SecurePasswordRecord Returns an object containing the encrypted password
         *                              along with associated cryptographic data such as IV and tag.
         * @throws CryptographyException Throws an exception if password encryption or
         *                                cryptographic element generation fails.
         * @throws \DateMalformedStringException
         */
        public static function securePassword(string $peerUuid, string $password, EncryptionRecord $record): SecurePasswordRecord
        {
            $decrypted = $record->decrypt();
            $saltedPassword = $decrypted->getSalt() . $password;
            $derivedKey = hash_pbkdf2('sha512', $saltedPassword, $decrypted->getPepper(), self::ITERATIONS, self::KEY_LENGTH / 8, true);

            try
            {
                $iv = random_bytes(openssl_cipher_iv_length(self::ENCRYPTION_ALGORITHM));
            }
            catch (RandomException $e)
            {
                throw new CryptographyException("Failed to generate IV for password encryption", $e);
            }

            $tag = null;
            $encryptedPassword = openssl_encrypt($derivedKey, self::ENCRYPTION_ALGORITHM, base64_decode($decrypted->getKey()), OPENSSL_RAW_DATA, $iv, $tag);

            if ($encryptedPassword === false)
            {
                throw new CryptographyException("Password encryption failed");
            }

            return new SecurePasswordRecord([
                'peer_uuid' => $peerUuid,
                'iv' => base64_encode($iv),
                'encrypted_password' => base64_encode($encryptedPassword),
                'encrypted_tag' => base64_encode($tag),
                'updated' => (new DateTime())->setTimestamp(time())
            ]);
        }

        /**
         * Verifies the provided password against the secured data and encryption records.
         *
         * @param string $input The user-provided password to be verified.
         * @param SecurePasswordRecord $secured An array containing encrypted data required for verification.
         * @param EncryptionRecord[] $encryptionRecords An array of encryption records used to perform decryption and validation.
         * @return bool Returns true if the password matches the secured data; otherwise, returns false.
         * @throws CryptographyException
         */
        public static function verifyPassword(string $input, SecurePasswordRecord $secured, array $encryptionRecords): bool
        {
            foreach ($encryptionRecords as $record)
            {
                $decrypted = $record->decrypt();
                $saltedInput = $decrypted->getSalt() . $input;
                $derivedKey = hash_pbkdf2('sha512', $saltedInput, $decrypted->getPepper(), self::ITERATIONS, self::KEY_LENGTH / 8, true);

                // Validation by re-encrypting and comparing
                $encryptedTag = base64_decode($secured->getEncryptedTag());
                $reEncryptedPassword = openssl_encrypt($derivedKey,
                    self::ENCRYPTION_ALGORITHM, base64_decode($decrypted->getKey()), OPENSSL_RAW_DATA,
                    base64_decode($secured->getIv()), $encryptedTag
                );

                if ($reEncryptedPassword !== false && hash_equals($reEncryptedPassword, base64_decode($secured->getEncryptedPassword())))
                {
                    return true;
                }
            }

            return false;
        }
    }
