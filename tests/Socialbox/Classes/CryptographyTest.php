<?php

    namespace Socialbox\Classes;

    use Exception;
    use PHPUnit\Framework\TestCase;
    use Socialbox\Exceptions\CryptographyException;
    use Socialbox\Objects\KeyPair;

    class CryptographyTest extends TestCase
    {
        /**
         * Test that generateEncryptionKeyPair generates a KeyPair with valid keys.
         */
        public function testGenerateEncryptionKeyPairProducesValidKeyPair(): void
        {
            $keyPair = Cryptography::generateEncryptionKeyPair();

            $this->assertInstanceOf(KeyPair::class, $keyPair);
            $this->assertNotEmpty($keyPair->getPublicKey());
            $this->assertNotEmpty($keyPair->getPrivateKey());
        }

        /**
         * Test that the generated public key starts with the defined encryption key type prefix.
         */
        public function testGeneratedPublicKeyHasEncryptionPrefix(): void
        {
            $keyPair = Cryptography::generateEncryptionKeyPair();

            $this->assertStringStartsWith('enc:', $keyPair->getPublicKey());
        }

        /**
         * Test that the generated private key starts with the defined encryption key type prefix.
         */
        public function testGeneratedPrivateKeyHasEncryptionPrefix(): void
        {
            $keyPair = Cryptography::generateEncryptionKeyPair();

            $this->assertStringStartsWith('enc:', $keyPair->getPrivateKey());
        }

        /**
         * Test that the generated keys are of different base64-encoded string values.
         */
        public function testPublicAndPrivateKeysAreDifferent(): void
        {
            $keyPair = Cryptography::generateEncryptionKeyPair();

            $this->assertNotEquals($keyPair->getPublicKey(), $keyPair->getPrivateKey());
        }


        /**
         * Test that generateSigningKeyPair generates a KeyPair with valid keys.
         */
        public function testGenerateSigningKeyPairProducesValidKeyPair(): void
        {
            $keyPair = Cryptography::generateSigningKeyPair();

            $this->assertInstanceOf(KeyPair::class, $keyPair);
            $this->assertNotEmpty($keyPair->getPublicKey());
            $this->assertNotEmpty($keyPair->getPrivateKey());
        }

        /**
         * Test that the generated public key starts with the defined signing key type prefix.
         */
        public function testGeneratedPublicKeyHasSigningPrefix(): void
        {
            $keyPair = Cryptography::generateSigningKeyPair();

            $this->assertStringStartsWith('sig:', $keyPair->getPublicKey());
        }

        /**
         * Test that the generated private key starts with the defined signing key type prefix.
         */
        public function testGeneratedPrivateKeyHasSigningPrefix(): void
        {
            $keyPair = Cryptography::generateSigningKeyPair();

            $this->assertStringStartsWith('sig:', $keyPair->getPrivateKey());
        }

        /**
         * Test that performDHE successfully calculates a shared secret with valid keys.
         */
        public function testPerformDHESuccessfullyCalculatesSharedSecret(): void
        {
            $aliceKeyPair = Cryptography::generateEncryptionKeyPair();
            $aliceSigningKeyPair = Cryptography::generateSigningKeyPair();
            $bobKeyPair = Cryptography::generateEncryptionKeyPair();
            $bobSigningKeyPair = Cryptography::generateSigningKeyPair();

            // Alice performs DHE with Bob
            $aliceSharedSecret = Cryptography::performDHE($bobKeyPair->getPublicKey(), $aliceKeyPair->getPrivateKey());
            // Bob performs DHE with Alice
            $bobSharedSecret = Cryptography::performDHE($aliceKeyPair->getPublicKey(), $bobKeyPair->getPrivateKey());
            $this->assertEquals($aliceSharedSecret, $bobSharedSecret);

            // Alice sends "Hello, Bob!" to Bob, signing the message and encrypting it with the shared secret
            $message = "Hello, Bob!";
            $aliceSignature = Cryptography::signMessage($message, $aliceSigningKeyPair->getPrivateKey());
            $encryptedMessage = Cryptography::encryptShared($message, $aliceSharedSecret);

            // Bob decrypts the message and verifies the signature
            $decryptedMessage = Cryptography::decryptShared($encryptedMessage, $bobSharedSecret);
            $isValid = Cryptography::verifyMessage($decryptedMessage, $aliceSignature, $aliceSigningKeyPair->getPublicKey());
            $this->assertEquals($message, $decryptedMessage);
            $this->assertTrue($isValid);

            // Bob sends "Hello, Alice!" to Alice, signing the message and encrypting it with the shared secret
            $message = "Hello, Alice!";
            $bobSignature = Cryptography::signMessage($message, $bobSigningKeyPair->getPrivateKey());
            $encryptedMessage = Cryptography::encryptShared($message, $bobSharedSecret);

            // Alice decrypts the message and verifies the signature
            $decryptedMessage = Cryptography::decryptShared($encryptedMessage, $aliceSharedSecret);
            $isValid = Cryptography::verifyMessage($decryptedMessage, $bobSignature, $bobSigningKeyPair->getPublicKey());
            $this->assertEquals($message, $decryptedMessage);
            $this->assertTrue($isValid);
        }

        /**
         * Test that performDHE throws an exception when an invalid public key is used.
         */
        public function testPerformDHEThrowsExceptionForInvalidPublicKey(): void
        {
            $encryptionKeyPair = Cryptography::generateEncryptionKeyPair();
            $invalidPublicKey = 'invalid_key';

            $this->expectException(CryptographyException::class);
            $this->expectExceptionMessage('Invalid key type. Expected enc:');

            Cryptography::performDHE($invalidPublicKey, $encryptionKeyPair->getPrivateKey());
        }

        /**
         * Test that performDHE throws an exception when an invalid private key is used.
         */
        public function testPerformDHEThrowsExceptionForInvalidPrivateKey(): void
        {
            $encryptionKeyPair = Cryptography::generateEncryptionKeyPair();
            $invalidPrivateKey = 'invalid_key';

            $this->expectException(CryptographyException::class);
            $this->expectExceptionMessage('Invalid key type. Expected enc:');

            Cryptography::performDHE($encryptionKeyPair->getPublicKey(), $invalidPrivateKey);
        }


        /**
         * Test that encrypt correctly encrypts a message with a valid shared secret.
         */
        public function testEncryptSuccessfullyEncryptsMessage(): void
        {
            $sharedSecret = Cryptography::performDHE(
                Cryptography::generateEncryptionKeyPair()->getPublicKey(),
                Cryptography::generateEncryptionKeyPair()->getPrivateKey()
            );
            $message = "Test message";

            $encryptedMessage = Cryptography::encryptShared($message, $sharedSecret);

            $this->assertNotEmpty($encryptedMessage);
            $this->assertNotEquals($message, $encryptedMessage);
        }

        /**
         * Test that encrypt throws an exception when given an invalid shared secret.
         */
        public function testEncryptThrowsExceptionForInvalidSharedSecret(): void
        {
            $invalidSharedSecret = "invalid_secret";
            $message = "Test message";

            $this->expectException(CryptographyException::class);
            $this->expectExceptionMessage("Encryption failed");

            Cryptography::encryptShared($message, $invalidSharedSecret);
        }

        /**
         * Test that the encrypted message is different from the original message.
         */
        public function testEncryptProducesDifferentMessage(): void
        {
            $sharedSecret = Cryptography::performDHE(
                Cryptography::generateEncryptionKeyPair()->getPublicKey(),
                Cryptography::generateEncryptionKeyPair()->getPrivateKey()
            );
            $message = "Another test message";

            $encryptedMessage = Cryptography::encryptShared($message, $sharedSecret);

            $this->assertNotEquals($message, $encryptedMessage);
        }

        /**
         * Test that decrypt successfully decrypts an encrypted message with a valid shared secret.
         */
        public function testDecryptSuccessfullyDecryptsMessage(): void
        {
            $sharedSecret = Cryptography::performDHE(
                Cryptography::generateEncryptionKeyPair()->getPublicKey(),
                Cryptography::generateEncryptionKeyPair()->getPrivateKey()
            );
            $message = "Decryption test message";

            $encryptedMessage = Cryptography::encryptShared($message, $sharedSecret);
            $decryptedMessage = Cryptography::decryptShared($encryptedMessage, $sharedSecret);

            $this->assertEquals($message, $decryptedMessage);
        }

        /**
         * Test that decrypt throws an exception when given an invalid shared secret.
         */
        public function testDecryptThrowsExceptionForInvalidSharedSecret(): void
        {
            $sharedSecret = Cryptography::performDHE(
                Cryptography::generateEncryptionKeyPair()->getPublicKey(),
                Cryptography::generateEncryptionKeyPair()->getPrivateKey()
            );
            $invalidSharedSecret = "invalid_shared_secret";
            $message = "Decryption failure case";

            $encryptedMessage = Cryptography::encryptShared($message, $sharedSecret);

            $this->expectException(CryptographyException::class);
            $this->expectExceptionMessage("Decryption failed");

            Cryptography::decryptShared($encryptedMessage, $invalidSharedSecret);
        }

        /**
         * Test that decrypt throws an exception when the encrypted data is tampered with.
         */
        public function testDecryptThrowsExceptionForTamperedEncryptedMessage(): void
        {
            $sharedSecret = Cryptography::performDHE(
                Cryptography::generateEncryptionKeyPair()->getPublicKey(),
                Cryptography::generateEncryptionKeyPair()->getPrivateKey()
            );
            $message = "Tampered message";

            $encryptedMessage = Cryptography::encryptShared($message, $sharedSecret);
            $tamperedMessage = $encryptedMessage . "tampered_data";

            $this->expectException(CryptographyException::class);
            $this->expectExceptionMessage("Decryption failed");

            Cryptography::decryptShared($tamperedMessage, $sharedSecret);
        }

        /**
         * Test that sign successfully signs a message with a valid private key.
         */
        public function testSignSuccessfullySignsMessage(): void
        {
            $keyPair = Cryptography::generateSigningKeyPair();
            $message = "Message to sign";

            $signature = Cryptography::signMessage($message, $keyPair->getPrivateKey());

            $this->assertNotEmpty($signature);
        }

        /**
         * Test that sign throws an exception when an invalid private key is used.
         */
        public function testSignThrowsExceptionForInvalidPrivateKey(): void
        {
            $invalidPrivateKey = "invalid_key";
            $message = "Message to sign";

            $this->expectException(CryptographyException::class);
            $this->expectExceptionMessage("Failed to sign message");

            Cryptography::signMessage($message, $invalidPrivateKey);
        }

        /**
         * Test that verify successfully validates a correct signature with a valid message and public key.
         */
        public function testVerifySuccessfullyValidatesSignature(): void
        {
            $keyPair = Cryptography::generateSigningKeyPair();
            $message = "Message to verify";
            $signature = Cryptography::signMessage($message, $keyPair->getPrivateKey());

            $isValid = Cryptography::verifyMessage($message, $signature, $keyPair->getPublicKey());

            $this->assertTrue($isValid);
        }

        /**
         * Test that verify fails for an invalid signature.
         */
        public function testVerifyFailsForInvalidSignature(): void
        {
            $keyPair = Cryptography::generateSigningKeyPair();
            $message = "Message to verify";
            $signature = "invalid_signature";

            $this->expectException(Exception::class);

            Cryptography::verifyMessage($message, $signature, $keyPair->getPublicKey());
        }

        /**
         * Test that verify throws an exception for an invalid public key.
         */
        public function testVerifyThrowsExceptionForInvalidPublicKey(): void
        {
            $keyPair = Cryptography::generateSigningKeyPair();
            $message = "Message to verify";
            $signature = Cryptography::signMessage($message, $keyPair->getPrivateKey());
            $invalidPublicKey = "invalid_public_key";

            $this->expectException(CryptographyException::class);
            $this->expectExceptionMessage("Failed to verify signature");

            Cryptography::verifyMessage($message, $signature, $invalidPublicKey);
        }

        /**
         * Test that verify throws an exception for a public key with the wrong type prefix.
         */
        public function testVerifyThrowsExceptionForInvalidKeyType(): void
        {
            $encryptionKeyPair = Cryptography::generateEncryptionKeyPair();
            $message = "Message to verify";
            $signature = "invalid_signature";

            $this->expectException(CryptographyException::class);
            $this->expectExceptionMessage("Invalid key type. Expected sig:");

            Cryptography::verifyMessage($message, $signature, $encryptionKeyPair->getPublicKey());
        }

        /**
         * Test that generateTransportKey creates a valid transport key for the default algorithm.
         */
        public function testGenerateTransportKeyCreatesValidKeyForDefaultAlgo(): void
        {
            $transportKey = Cryptography::generateEncryptionKey();
            $decodedKey = sodium_base642bin($transportKey, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING, true);

            $this->assertNotEmpty($transportKey);
            $this->assertEquals(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES, strlen($decodedKey));
        }

        /**
         * Test that generateTransportKey creates valid keys for specific supported algorithms.
         */
        public function testGenerateTransportKeyCreatesValidKeysForAlgorithms(): void
        {
            $algorithms = [
                'xchacha20' => SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES,
                'chacha20' => SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_KEYBYTES,
                'aes256gcm' => SODIUM_CRYPTO_AEAD_AES256GCM_KEYBYTES
            ];

            foreach ($algorithms as $algorithm => $expectedKeyLength) {
                $transportKey = Cryptography::generateEncryptionKey($algorithm);
                $decodedKey = sodium_base642bin($transportKey, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING, true);

                $this->assertNotEmpty($transportKey);
                $this->assertEquals($expectedKeyLength, strlen($decodedKey));
            }
        }

        /**
         * Test that generateTransportKey throws an exception when given an invalid algorithm.
         */
        public function testGenerateTransportKeyThrowsExceptionForInvalidAlgorithm(): void
        {
            $this->expectException(CryptographyException::class);
            $this->expectExceptionMessage("Unsupported algorithm");

            Cryptography::generateEncryptionKey("invalid_algorithm");
        }

        /**
         * Test that generateTransportKey creates valid keys for other supported algorithms.
         */
        public function testGenerateTransportKeyCreatesValidKeyForOtherSupportedAlgorithms(): void
        {
            $algorithms = [
                'xchacha20' => SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES,
                'chacha20' => SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_KEYBYTES,
                'aes256gcm' => SODIUM_CRYPTO_AEAD_AES256GCM_KEYBYTES
            ];

            foreach ($algorithms as $algorithm => $expectedKeyLength) {
                $transportKey = Cryptography::generateEncryptionKey($algorithm);
                $decodedKey = sodium_base642bin($transportKey, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING, true);

                $this->assertNotEmpty($transportKey);
                $this->assertEquals($expectedKeyLength, strlen($decodedKey));
            }
        }

        /**
         * Test that generateTransportKey throws an exception for unsupported algorithms.
         */
        public function testGenerateTransportKeyThrowsExceptionForUnsupportedAlgorithm(): void
        {
            $this->expectException(CryptographyException::class);
            $this->expectExceptionMessage("Unsupported algorithm");

            Cryptography::generateEncryptionKey('invalid_algo');
        }

        public function testEncryptTransportMessageSuccessfullyEncryptsMessage(): void
        {
            $algorithms = [
                'xchacha20' => SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES,
                'chacha20' => SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_KEYBYTES,
                'aes256gcm' => SODIUM_CRYPTO_AEAD_AES256GCM_KEYBYTES
            ];

            foreach ($algorithms as $algorithm => $keyLength) {
                $transportKey = Cryptography::generateEncryptionKey($algorithm);
                $this->assertNotEmpty($transportKey);
                $this->assertEquals($keyLength, strlen(sodium_base642bin($transportKey, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING, true)));
                $message = "Test message";

                $encryptedMessage = Cryptography::encryptMessage($message, $transportKey);
                $decryptedMessage = Cryptography::decryptMessage($encryptedMessage, $transportKey);

                $this->assertEquals($message, $decryptedMessage);
            }
        }
    }