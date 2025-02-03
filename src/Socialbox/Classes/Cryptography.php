<?php

    namespace Socialbox\Classes;

    use Exception;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Objects\KeyPair;
    use SodiumException;

    class Cryptography
    {
        private const string KEY_TYPE_ENCRYPTION = 'enc:';
        private const string KEY_TYPE_SIGNING = 'sig:';
        private const int BASE64_VARIANT = SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING;

        /**
         * Generates a new encryption key pair consisting of a public key and a secret key.
         * The generated keys are encoded in a specific format and securely handled in memory.
         *
         * @return KeyPair Returns an instance of KeyPair containing the encoded public and secret keys.
         * @throws CryptographyException If key pair generation fails.
         */
        public static function generateEncryptionKeyPair(): KeyPair
        {
            try
            {
                $keyPair = sodium_crypto_box_keypair();
                $publicKey = sodium_crypto_box_publickey($keyPair);
                $secretKey = sodium_crypto_box_secretkey($keyPair);

                $result = new KeyPair(
                    self::KEY_TYPE_ENCRYPTION . sodium_bin2base64($publicKey, self::BASE64_VARIANT),
                    self::KEY_TYPE_ENCRYPTION . sodium_bin2base64($secretKey, self::BASE64_VARIANT)
                );

                // Clean up sensitive data
                sodium_memzero($keyPair);
                sodium_memzero($secretKey);

                return $result;
            }
            catch (Exception $e)
            {
                throw new CryptographyException("Failed to generate encryption keypair: " . $e->getMessage());
            }
        }

        /**
         * Validates a public encryption key to ensure it is properly formatted and of the correct length.
         *
         * @param string $publicKey The base64-encoded public key to validate.
         * @return bool True if the public key is valid, false otherwise.
         */
        public static function validatePublicEncryptionKey(string $publicKey): bool
        {
            if(!str_starts_with($publicKey, self::KEY_TYPE_ENCRYPTION))
            {
                return false;
            }

            $base64Key = substr($publicKey, 4);

            try
            {
                $decodedKey = sodium_base642bin($base64Key, self::BASE64_VARIANT, true);

                if (strlen($decodedKey) !== SODIUM_CRYPTO_BOX_PUBLICKEYBYTES)
                {
                    return false;
                }

                return true;
            }
            catch (Exception)
            {
                return false;
            }
        }

        /**
         * Generates a new signing key pair consisting of a public key and a secret key.
         *
         * @return KeyPair An object containing the base64-encoded public and secret keys, each prefixed with the signing key type identifier.
         * @throws CryptographyException If the key pair generation process fails.
         */
        public static function generateSigningKeyPair(): KeyPair
        {
            try
            {
                $keyPair = sodium_crypto_sign_keypair();
                $publicKey = sodium_crypto_sign_publickey($keyPair);
                $secretKey = sodium_crypto_sign_secretkey($keyPair);

                $result = new KeyPair(
                    self::KEY_TYPE_SIGNING . sodium_bin2base64($publicKey, self::BASE64_VARIANT),
                    self::KEY_TYPE_SIGNING . sodium_bin2base64($secretKey, self::BASE64_VARIANT)
                );

                // Clean up sensitive data
                sodium_memzero($keyPair);
                sodium_memzero($secretKey);

                return $result;
            }
            catch (Exception $e)
            {
                throw new CryptographyException("Failed to generate signing keypair: " . $e->getMessage());
            }
        }

        /**
         * Validates a public signing key for proper format and length.
         *
         * @param string $publicKey The base64-encoded public signing key to be validated.
         * @return bool Returns true if the key is valid, or false if it is invalid.
         */
        public static function validatePublicSigningKey(string $publicKey): bool
        {
            // Check if the key is prefixed with "sig:"
            if (!str_starts_with($publicKey, self::KEY_TYPE_SIGNING))
            {
                // If it doesn't start with "sig:", consider it invalid
                return false;
            }

            // Remove the "sig:" prefix
            $base64Key = substr($publicKey, 4);

            try
            {
                // Decode the base64 key
                $decodedKey = sodium_base642bin($base64Key, self::BASE64_VARIANT, true);

                // Validate the length of the decoded key
                return strlen($decodedKey) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES;
            }
            catch (Exception)
            {
                // If decoding fails, consider the key invalid
                return false;
            }
        }

        /**
         * Performs a Diffie-Hellman Exchange (DHE) to derive a shared secret key using the provided public and private keys.
         *
         * @param string $publicKey The base64-encoded public key of the other party.
         * @param string $privateKey The base64-encoded private key of the local party.
         * @return string The base64-encoded derived shared secret key.
         * @throws CryptographyException If the provided keys are invalid or the key exchange process fails.
         */
        public static function performDHE(string $publicKey, string $privateKey): string
        {
            try
            {
                if (empty($publicKey) || empty($privateKey))
                {
                    throw new CryptographyException("Empty key(s) provided");
                }

                $publicKey = self::validateAndExtractKey($publicKey, self::KEY_TYPE_ENCRYPTION);
                $privateKey = self::validateAndExtractKey($privateKey, self::KEY_TYPE_ENCRYPTION);

                $decodedPublicKey = sodium_base642bin($publicKey, self::BASE64_VARIANT, true);
                $decodedPrivateKey = sodium_base642bin($privateKey, self::BASE64_VARIANT, true);

                if (strlen($decodedPublicKey) !== SODIUM_CRYPTO_BOX_PUBLICKEYBYTES)
                {
                    throw new CryptographyException("Invalid public key length");
                }

                if (strlen($decodedPrivateKey) !== SODIUM_CRYPTO_BOX_SECRETKEYBYTES)
                {
                    throw new CryptographyException("Invalid private key length");
                }

                $sharedSecret = sodium_crypto_scalarmult($decodedPrivateKey, $decodedPublicKey);
                $derivedKey = sodium_crypto_generichash($sharedSecret, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
                $result = sodium_bin2base64($derivedKey, self::BASE64_VARIANT);

                // Clean up sensitive data
                sodium_memzero($sharedSecret);
                sodium_memzero($derivedKey);
                sodium_memzero($decodedPrivateKey);

                return $result;
            }
            catch (Exception $e)
            {
                throw new CryptographyException("Failed to perform DHE: " . $e->getMessage());
            }
        }

        /**
         * Encrypts a message using the provided shared secret.
         *
         * @param string $message The message to be encrypted.
         * @param string $sharedSecret The base64-encoded shared secret used for encryption.
         * @return string The base64-encoded encrypted message, including a randomly generated nonce.
         * @throws CryptographyException If the message or shared secret is invalid or the encryption fails.
         */
        public static function encryptShared(string $message, string $sharedSecret): string
        {
            try
            {
                if (empty($message))
                {
                    throw new CryptographyException("Empty message provided");
                }

                if (empty($sharedSecret))
                {
                    throw new CryptographyException("Empty shared secret provided");
                }

                $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
                $key = sodium_base642bin($sharedSecret, self::BASE64_VARIANT, true);

                if (strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
                {
                    throw new CryptographyException("Invalid shared secret length");
                }

                $encrypted = sodium_crypto_secretbox($message, $nonce, $key);
                $result = sodium_bin2base64($nonce . $encrypted, self::BASE64_VARIANT);

                // Clean up sensitive data
                sodium_memzero($key);

                return $result;
            }
            catch (Exception $e)
            {
                throw new CryptographyException("Encryption failed: " . $e->getMessage());
            }
        }

        /**
         * Decrypts an encrypted message using the provided shared secret.
         *
         * @param string $encryptedMessage The base64-encoded encrypted message to be decrypted.
         * @param string $sharedSecret The base64-encoded shared secret used to decrypt the message.
         * @return string The decrypted message.
         * @throws CryptographyException If the encrypted message or shared secret is invalid, or the decryption process fails.
         */
        public static function decryptShared(string $encryptedMessage, string $sharedSecret): string
        {
            try
            {
                if (empty($encryptedMessage))
                {
                    throw new CryptographyException("Empty encrypted message provided");
                }

                if (empty($sharedSecret))
                {
                    throw new CryptographyException("Empty shared secret provided");
                }

                $decoded = sodium_base642bin($encryptedMessage, self::BASE64_VARIANT, true);
                $key = sodium_base642bin($sharedSecret, self::BASE64_VARIANT, true);

                if (strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
                {
                    throw new CryptographyException("Invalid shared secret length");
                }

                if (strlen($decoded) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES)
                {
                    throw new CryptographyException("Encrypted message too short");
                }

                $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
                $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

                $decrypted = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);

                if ($decrypted === false)
                {
                    throw new CryptographyException("Decryption failed: Invalid message or shared secret");
                }

                sodium_memzero($key);
                return $decrypted;
            }
            catch (Exception $e)
            {
                throw new CryptographyException("Decryption failed: " . $e->getMessage());
            }
        }

        /**
         * Signs a message using the provided private key.
         *
         * @param string $message The message to be signed.
         * @param string $privateKey The base64-encoded private key used for signing.
         * @return string The base64-encoded digital signature.
         * @throws CryptographyException If the message or private key is invalid, or if signing fails.
         */
        public static function signMessage(string $message, string $privateKey, bool $hash=true): string
        {
            if (empty($message))
            {
                throw new CryptographyException("Empty message provided");
            }

            if (empty($privateKey))
            {
                throw new CryptographyException("Empty private key provided");
            }

            if($hash)
            {
                $message = hash('sha512', $message);
            }
            else
            {
                if(!self::validateSha512($message))
                {
                    throw new CryptographyException("Invalid SHA-512 hash provided");
                }
            }

            try
            {
                $privateKey = self::validateAndExtractKey($privateKey, self::KEY_TYPE_SIGNING);
                $decodedKey = sodium_base642bin($privateKey, self::BASE64_VARIANT, true);

                if (strlen($decodedKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES)
                {
                    throw new CryptographyException("Invalid private key length");
                }

                $signature = sodium_crypto_sign_detached($message, $decodedKey);

                sodium_memzero($decodedKey);
                return sodium_bin2base64($signature, self::BASE64_VARIANT);
            }
            catch (Exception $e)
            {
                if($e instanceof CryptographyException)
                {
                    throw $e;
                }

                throw new CryptographyException("Failed to sign message: " . $e->getMessage());
            }
        }

        /**
         * Signs a message using the provided private key and a timestamp.
         *
         * @param string $message The message to be signed.
         * @param string $privateKey The base64-encoded private key used for signing.
         * @param int $timestamp The timestamp to be included in the signed message.
         * @param bool $hash True to hash the message before signing, false to use the message directly.
         * @return string The base64-encoded digital signature.
         * @throws CryptographyException If the message or private key is invalid, or if signing fails.
         */
        public static function signTimedMessage(string $message, string $privateKey, int $timestamp, bool $hash=true): string
        {
            if (empty($message))
            {
                throw new CryptographyException("Empty message provided");
            }

            if($timestamp <= 0)
            {
                throw new CryptographyException("Invalid timestamp provided");
            }

            if($hash)
            {
                $message = hash('sha512', $message);
            }
            else
            {
                if(!self::validateSha512($message))
                {
                    throw new CryptographyException("Invalid SHA-512 hash provided");
                }
            }

            return self::signMessage(sprintf("digest:%s;timestamp:%d", $message, $timestamp), $privateKey);
        }

        /**
         * Verifies the validity of a given signature for a message using the provided public key.
         *
         * @param string $message The original message that was signed.
         * @param string $signature The base64-encoded signature to be verified.
         * @param string $publicKey The base64-encoded public key used to verify the signature.
         * @param bool $hash True to hash the message before verification, false to use the message directly.
         * @return bool True if the signature is valid; false otherwise.
         * @throws CryptographyException If any parameter is empty, if the public key or signature is invalid, or if the verification process fails.
         */
        public static function verifyMessage(string $message, string $signature, string $publicKey, bool $hash=true): bool
        {
            if (empty($message) || empty($signature) || empty($publicKey))
            {
                throw new CryptographyException("Empty parameter(s) provided");
            }

            if($hash)
            {
                $message = hash('sha512', $message);
            }
            else
            {
                if(!self::validateSha512($message))
                {
                    throw new CryptographyException("Invalid SHA-512 hash provided");
                }
            }

            try
            {
                $publicKey = self::validateAndExtractKey($publicKey, self::KEY_TYPE_SIGNING);
                $decodedKey = sodium_base642bin($publicKey, self::BASE64_VARIANT, true);
                $decodedSignature = sodium_base642bin($signature, self::BASE64_VARIANT, true);

                if (strlen($decodedKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES)
                {
                    throw new CryptographyException("Invalid public key length");
                }

                if (strlen($decodedSignature) !== SODIUM_CRYPTO_SIGN_BYTES)
                {
                    throw new CryptographyException("Invalid signature length");
                }

                return sodium_crypto_sign_verify_detached($decodedSignature, $message, $decodedKey);
            }
            catch (Exception $e)
            {
                if($e instanceof CryptographyException)
                {
                    throw $e;
                }

                throw new CryptographyException("Failed to verify signature: " . $e->getMessage());
            }
        }

        /**
         * Verifies the validity of a given signature for a message using the provided public key and timestamp.
         *
         * @param string $message The original message that was signed.
         * @param string $signature The base64-encoded signature to be verified.
         * @param string $publicKey The base64-encoded public key used to verify the signature.
         * @param int $timestamp The timestamp to be included in the signed message.
         * @param bool $hash True to hash the message before verification, false to use the message directly.
         * @return bool True if the signature is valid; false otherwise.
         * @throws CryptographyException If any parameter is empty, if the public key or signature is invalid, or if the verification process fails.
         */
        public static function verifyTimedMessage(string $message, string $signature, string $publicKey, int $timestamp, bool $hash=true): bool
        {
            if (empty($message) || empty($signature) || empty($publicKey))
            {
                throw new CryptographyException("Empty parameter(s) provided");
            }

            if($timestamp <= 0)
            {
                throw new CryptographyException("Invalid timestamp provided");
            }

            if($hash)
            {
                $message = hash('sha512', $message);
            }
            else
            {
                if(!self::validateSha512($message))
                {
                    throw new CryptographyException("Invalid SHA-512 hash provided");
                }
            }

            return self::verifyMessage(sprintf("digest:%s;timestamp:%d", $message, $timestamp), $signature, $publicKey);
        }

        /**
         * Determines if the provided algorithm is supported.
         *
         * @param string $algorithm The name of the algorithm to check.
         * @return bool True if the algorithm is supported, false otherwise.
         */
        public static function isSupportedAlgorithm(string $algorithm): bool
        {
            return match($algorithm)
            {
                'xchacha20', 'chacha20', 'aes256gcm' => true,
                default => false
            };
        }

        /**
         * Generates a new encryption key for the specified algorithm.
         *
         * @param string $algorithm The encryption algorithm for which the key is generated.
         *                          Supported values are 'xchacha20', 'chacha20', and 'aes256gcm'.
         * @return string The base64-encoded encryption key.
         * @throws CryptographyException If the algorithm is unsupported or if key generation fails.
         */
        public static function generateEncryptionKey(string $algorithm): string
        {
            if(!self::isSupportedAlgorithm($algorithm))
            {
                throw new CryptographyException('Unsupported Algorithm: ' . $algorithm);
            }

            try
            {
                $keygenMethod = match ($algorithm)
                {
                    'xchacha20' => 'sodium_crypto_aead_xchacha20poly1305_ietf_keygen',
                    'chacha20' => 'sodium_crypto_aead_chacha20poly1305_keygen',
                    'aes256gcm' => 'sodium_crypto_aead_aes256gcm_keygen',
                };

                return sodium_bin2base64($keygenMethod(), self::BASE64_VARIANT);
            }
            catch (Exception $e)
            {
                if($e instanceof CryptographyException)
                {
                    throw $e;
                }

                throw new CryptographyException("Failed to generate encryption key: " . $e->getMessage());
            }
        }

        /**
         * Validates the provided encryption key against the specified algorithm.
         *
         * @param string $encryptionKey The encryption key to be validated, encoded in Base64.
         * @param string $algorithm The encryption algorithm that the key should match.
         *                          Supported algorithms include 'xchacha20', 'chacha20', and 'aes256gcm'.
         * @return bool Returns true if the encryption key is valid for the given algorithm, otherwise false.
         */
        public static function validateEncryptionKey(string $encryptionKey, string $algorithm): bool
        {
            if (empty($encryptionKey))
            {
                return false;
            }

            if(!self::isSupportedAlgorithm($algorithm))
            {
                return false;
            }

            try
            {
                $key = sodium_base642bin($encryptionKey, self::BASE64_VARIANT, true);
                $keyLength = match ($algorithm)
                {
                    'xchacha20' => SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES,
                    'chacha20' => SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_KEYBYTES,
                    'aes256gcm' => SODIUM_CRYPTO_AEAD_AES256GCM_KEYBYTES
                };

                if (strlen($key) !== $keyLength)
                {
                    return false;
                }

                return true;
            }
            catch (Exception)
            {
                return false;
            }
            finally
            {
                if (isset($key))
                {
                    try
                    {
                        sodium_memzero($key);
                    }
                    catch (SodiumException)
                    {
                        // Ignore
                    }
                }
            }
        }

        /**
         * Encrypts a message using the specified encryption algorithm and key.
         *
         * @param string $message The plaintext message to be encrypted.
         * @param string $encryptionKey A base64-encoded encryption key.
         * @param string $algorithm The name of the encryption algorithm to be used (e.g., 'xchacha20', 'chacha20', 'aes256gcm').
         * @return string The base64-encoded encrypted message including the nonce.
         * @throws CryptographyException If the message, encryption key, or algorithm is invalid, or if encryption fails.
         */
        public static function encryptMessage(string $message, string $encryptionKey, string $algorithm): string
        {
            try
            {
                if (empty($message))
                {
                    throw new CryptographyException("Empty message provided");
                }

                if (empty($encryptionKey))
                {
                    throw new CryptographyException("Empty encryption key provided");
                }

                if(!self::isSupportedAlgorithm($algorithm))
                {
                    throw new CryptographyException('Unsupported Algorithm: ' . $algorithm);
                }

                $key = sodium_base642bin($encryptionKey, self::BASE64_VARIANT, true);

                [$nonceLength, $encryptMethod, $keyLength] = match ($algorithm)
                {
                    'xchacha20' => [SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES, 'sodium_crypto_aead_xchacha20poly1305_ietf_encrypt', SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES],
                    'chacha20' => [SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_NPUBBYTES, 'sodium_crypto_aead_chacha20poly1305_encrypt', SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_KEYBYTES],
                    'aes256gcm' => [SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES, 'sodium_crypto_aead_aes256gcm_encrypt', SODIUM_CRYPTO_AEAD_AES256GCM_KEYBYTES],
                };

                if (strlen($key) !== $keyLength)
                {
                    throw new CryptographyException("Invalid encryption key length for $algorithm");
                }

                $nonce = random_bytes($nonceLength);
                $encrypted = $encryptMethod($message, '', $nonce, $key);
                return sodium_bin2base64($nonce . $encrypted, self::BASE64_VARIANT);
            }
            catch (Exception $e)
            {
                if($e instanceof CryptographyException)
                {
                    throw $e;
                }

                throw new CryptographyException("Message encryption failed: " . $e->getMessage());
            }
            finally
            {
                if (isset($key))
                {
                    try
                    {
                        sodium_memzero($key);
                    }
                    catch (SodiumException)
                    {
                        // Ignore
                    }
                }
            }
        }

        /**
         * Decrypts an encrypted message using the specified encryption key and algorithm.
         *
         * @param string $encryptedMessage The base64-encoded encrypted message to be decrypted.
         * @param string $encryptionKey The base64-encoded encryption key used for decryption.
         * @param string $algorithm The encryption algorithm used to encrypt the message (e.g., 'xchacha20', 'chacha20', 'aes256gcm').
         * @return string The decrypted plaintext message.
         * @throws CryptographyException If the encrypted message, encryption key, or algorithm is invalid, or if decryption fails.
         */
        public static function decryptMessage(string $encryptedMessage, string $encryptionKey, string $algorithm): string
        {
            if (empty($encryptedMessage))
            {
                throw new CryptographyException("Empty encrypted message provided");
            }

            if (empty($encryptionKey))
            {
                throw new CryptographyException("Empty encryption key provided");
            }

            if(!self::isSupportedAlgorithm($algorithm))
            {
                throw new CryptographyException('Unsupported Algorithm: ' . $algorithm);
            }

            try
            {

                $key = sodium_base642bin($encryptionKey, self::BASE64_VARIANT, true);
                [$nonceLength, $decryptMethod, $keyLength] = match ($algorithm)
                {
                    'xchacha20' => [SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES, 'sodium_crypto_aead_xchacha20poly1305_ietf_decrypt', SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES],
                    'chacha20' => [SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_NPUBBYTES, 'sodium_crypto_aead_chacha20poly1305_decrypt', SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_KEYBYTES],
                    'aes256gcm' => [SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES, 'sodium_crypto_aead_aes256gcm_decrypt', SODIUM_CRYPTO_AEAD_AES256GCM_KEYBYTES]
                };

                if (strlen($key) !== $keyLength)
                {
                    throw new CryptographyException("Invalid encryption key length for $algorithm");
                }

                $decoded = sodium_base642bin($encryptedMessage, self::BASE64_VARIANT, true);

                if (strlen($decoded) < $nonceLength)
                {
                    throw new CryptographyException("Encrypted message is too short");
                }

                $nonce = mb_substr($decoded, 0, $nonceLength, '8bit');
                $ciphertext = mb_substr($decoded, $nonceLength, null, '8bit');
                $decrypted = $decryptMethod($ciphertext, '', $nonce, $key);

                if ($decrypted === false)
                {
                    throw new CryptographyException("Invalid message or encryption key");
                }

                return $decrypted;
            }
            catch (Exception $e)
            {
                if($e instanceof CryptographyException)
                {
                    throw $e;
                }

                throw new CryptographyException("Message decryption failed: " . $e->getMessage());
            }
            finally
            {
                if (isset($key))
                {
                    try
                    {
                        sodium_memzero($key);
                    }
                    catch (SodiumException)
                    {
                        // Ignore
                    }
                }
            }
        }

        /**
         * Validates whether the provided hash is a valid SHA-512 hash.
         *
         * @param string $hash The hash string to be validated, expected to be a 128-character hexadecimal string.
         * @return bool Returns true if the hash is a valid SHA-512 hash, otherwise false.
         */
        public static function validateSha512(string $hash): bool
        {
            return preg_match('/^[A-Fa-f0-9]{128}$/', $hash) === 1;
        }

        /**
         * Hashes a password securely using a memory-hard, CPU-intensive hashing algorithm.
         *
         * @param string $password The password in plaintext to be hashed, if $hash is false this should be a SHA-512 hash.
         * @param bool $hash True to hash the password, false to use the provided SHA-512 hash directly which is validated.
         * @return string The hashed password in a secure format.
         * @throws CryptographyException If password hashing fails.
         */
        public static function hashPassword(string $password, bool $hash=true): string
        {
            if(empty($password))
            {
                throw new CryptographyException("Empty password provided");
            }

            if($hash === false && !self::validateSha512($password))
            {
                throw new CryptographyException("Invalid SHA-512 hash provided");
            }

            if($hash)
            {
                $sha512 = hash('sha512', $password);
            }
            
            try
            {
                return sodium_crypto_pwhash_str($sha512, SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE, SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE);
            }
            catch (Exception $e)
            {
                throw new CryptographyException("Failed to hash password: " . $e->getMessage());
            }
        }

        /**
         * Validates the given Argon2id hash string based on its format and current security requirements.
         *
         * @param string $hash The hash string to be validated.
         * @return bool Returns true if the hash is valid and meets current security standards.
         */
        public static function validatePasswordHash(string $hash): bool
        {
            try
            {
                // Step 1: Check the format
                $argon2id_pattern = '/^\$argon2id\$v=\d+\$m=\d+,t=\d+,p=\d+\$[A-Za-z0-9+\/=]+\$[A-Za-z0-9+\/=]+$/D';
                if (!preg_match($argon2id_pattern, $hash))
                {
                    return false;
                }

                // Step 2: Check if it needs rehashing (validates the hash structure)
                if (sodium_crypto_pwhash_str_needs_rehash($hash, SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE, SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE))
                {
                    return false;
                }
            }
            catch (Exception)
            {
                return false;
            }

            return true;
        }

        /**
         * Verifies a password against a stored hash.
         *
         * @param string $sha512 The sha512 password to be verified.
         * @param string $hash The stored password hash to be compared against.
         * @return bool True if the password matches the hash; false otherwise.
         * @throws CryptographyException If the password verification process fails.
         */
        public static function verifyPassword(string $sha512, string $hash): bool
        {
            if(!self::validateSha512($sha512))
            {
                throw new CryptographyException("Invalid password hash provided");
            }
            
            if(!self::validatePasswordHash($hash))
            {
                throw new CryptographyException("Invalid password hash provided");
            }

            try
            {
                return sodium_crypto_pwhash_str_verify($hash, $sha512);
            }
            catch (Exception $e)
            {
                throw new CryptographyException("Failed to verify password: " . $e->getMessage());
            }
        }

        /**
         * Validates a key by ensuring it is not empty, matches the expected type, and extracts the usable portion.
         *
         * @param string $key The key to be validated and processed.
         * @param string $expectedType The expected prefix type of the key.
         * @return string The extracted portion of the key after the expected type.
         * @throws CryptographyException If the key is empty, the key type is invalid, or the extracted portion is empty.
         */
        private static function validateAndExtractKey(string $key, string $expectedType): string
        {
            if (empty($key))
            {
                throw new CryptographyException("Empty key provided");
            }

            if (!str_starts_with($key, $expectedType))
            {
                throw new CryptographyException("Invalid key type. Expected {$expectedType}");
            }

            $extractedKey = substr($key, strlen($expectedType));
            if (empty($extractedKey))
            {
                throw new CryptographyException("Empty key after type extraction");
            }

            return $extractedKey;
        }
    }