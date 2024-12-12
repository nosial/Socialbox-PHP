<?php

namespace Socialbox\Classes;

use InvalidArgumentException;
use Random\RandomException;
use Socialbox\Exceptions\CryptographyException;
use Socialbox\Objects\KeyPair;

class Cryptography
{
    private const int TIME_BLOCK = 60;
    private const int KEY_SIZE = 2048;
    private const int ALGORITHM = OPENSSL_KEYTYPE_RSA;
    private const int HASH_ALGORITHM = OPENSSL_ALGO_SHA256;
    private const int PADDING = OPENSSL_PKCS1_OAEP_PADDING;
    private const string PEM_PRIVATE_HEADER = 'PRIVATE';
    private const string PEM_PUBLIC_HEADER = 'PUBLIC';
    private const string TRANSPORT_ENCRYPTION = 'aes-256-cbc';

    /**
     * Generates a new public-private key pair.
     *
     * @return KeyPair The generated key pair, with the keys encoded in base64 DER format.
     * @throws CryptographyException If an error occurs during key generation.
     */
    public static function generateKeyPair(): KeyPair
    {
        $config = [
            "private_key_type" => self::ALGORITHM,
            "private_key_bits" => self::KEY_SIZE,
        ];

        $res = openssl_pkey_new($config);
        if (!$res)
        {
            throw new CryptographyException('Failed to generate private key: ' . openssl_error_string());
        }

        openssl_pkey_export($res, $privateKeyPem);
        $publicKeyPem = openssl_pkey_get_details($res)['key'];

        return new KeyPair(
            Utilities::base64encode(self::pemToDer($publicKeyPem)),
            Utilities::base64encode(self::pemToDer($privateKeyPem))
        );
    }

    /**
     * Converts a PEM formatted key to DER format.
     *
     * @param string $pemKey The PEM formatted key as a string.
     *
     * @return string The DER formatted key as a binary string.
     */
    private static function pemToDer(string $pemKey): string
    {
        $pemKey = preg_replace('/-----(BEGIN|END) [A-Z ]+-----/', '', $pemKey);
        return Utilities::base64decode(str_replace(["\n", "\r", " "], '', $pemKey));
    }

    /**
     * Converts a DER formatted key to PEM format.
     *
     * @param string $derKey The DER formatted key.
     * @param string $type The type of key, either private or public. Default is private.
     * @return string The PEM formatted key.
     */
    private static function derToPem(string $derKey, string $type): string
    {
        $formattedKey = chunk_split(Utilities::base64encode($derKey), 64);
        $headerFooter = strtoupper($type) === self::PEM_PUBLIC_HEADER
            ? "PUBLIC KEY" : "PRIVATE KEY";

        return "-----BEGIN $headerFooter-----\n$formattedKey-----END $headerFooter-----\n";
    }

    /**
     * Signs the given content using the provided private key.
     *
     * @param string $content The content to be signed.
     * @param string $privateKey The private key used to sign the content.
     * @param bool $hashContent Whether to hash the content using SHA1 before signing it. Default is false.
     * @return string The Base64 encoded signature of the content.
     * @throws CryptographyException If the private key is invalid or if the content signing fails.
     */
    public static function signContent(string $content, string $privateKey, bool $hashContent=false): string
    {
        $privateKey = openssl_pkey_get_private(self::derToPem(Utilities::base64decode($privateKey), self::PEM_PRIVATE_HEADER));
        if (!$privateKey)
        {
            throw new CryptographyException('Invalid private key: ' . openssl_error_string());
        }

        if($hashContent)
        {
            $content = hash('sha1', $content);
        }

        if (!openssl_sign($content, $signature, $privateKey, self::HASH_ALGORITHM))
        {
            throw new CryptographyException('Failed to sign content: ' . openssl_error_string());
        }

        return base64_encode($signature);
    }

    /**
     * Verifies the integrity of the given content using the provided digital signature and public key.
     *
     * @param string $content The content to be verified.
     * @param string $signature The digital signature to verify against.
     * @param string $publicKey The public key to use for verification.
     * @param bool $hashContent Whether to hash the content using SHA1 before verifying it. Default is false.
     * @return bool Returns true if the content verification is successful, false otherwise.
     * @throws CryptographyException If the public key is invalid or if the signature verification fails.
     */
    public static function verifyContent(string $content, string $signature, string $publicKey, bool $hashContent=false): bool
    {
        try
        {
            $publicKey = openssl_pkey_get_public(self::derToPem(Utilities::base64decode($publicKey), self::PEM_PUBLIC_HEADER));
        }
        catch(InvalidArgumentException $e)
        {
            throw new CryptographyException('Failed to decode public key: ' . $e->getMessage());
        }

        if (!$publicKey)
        {
            throw new CryptographyException('Invalid public key: ' . openssl_error_string());
        }

        if($hashContent)
        {
            $content = hash('sha1', $content);
        }

        try
        {
            return openssl_verify($content, Utilities::base64decode($signature), $publicKey, self::HASH_ALGORITHM) === 1;
        }
        catch(InvalidArgumentException $e)
        {
            throw new CryptographyException('Failed to verify content: ' . $e->getMessage());
        }
    }

    /**
     * Temporarily signs the provided content by appending a timestamp-based value and signing it.
     *
     * @param string $content The content to be signed.
     * @param string $privateKey The private key used to sign the content.
     * @return string The base64 encoded signature of the content with the appended timestamp.
     * @throws CryptographyException If the private key is invalid or if the content signing fails.
     */
    public static function temporarySignContent(string $content, string $privateKey): string
    {
        return self::signContent(sprintf('%s|%d', $content, time() / self::TIME_BLOCK), $privateKey);
    }

    /**
     * Verify the provided temporary signature for the given content using the public key.
     *
     * @param string $content The content whose signature needs to be verified.
     * @param string $signature The signature associated with the content.
     * @param string $publicKey The public key to be used for verifying the signature.
     * @param int $frames The number of time frames to consider for validating the signature (default is 1).
     * @return bool Returns true if the signature is valid within the provided time frames, otherwise false.
     * @throws CryptographyException If the public key is invalid or the signature verification fails.
     */
    public static function verifyTemporarySignature(string $content, string $signature, string $publicKey, int $frames = 1): bool
    {
        $currentTime = time() / self::TIME_BLOCK;
        for ($i = 0; $i < max(1, $frames); $i++)
        {
            if (self::verifyContent(sprintf('%s|%d', $content, $currentTime - $i), $signature, $publicKey))
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Encrypts the given content using the provided public key.
     *
     * @param string $content The content to be encrypted.
     * @param string $publicKey The public key used for encryption, in DER-encoded format.
     * @return string The encrypted content, encoded in base64 format.
     * @throws CryptographyException If the public key is invalid or the encryption fails.
     */
    public static function encryptContent(string $content, string $publicKey): string
    {
        $publicKey = openssl_pkey_get_public(self::derToPem(Utilities::base64decode($publicKey), self::PEM_PUBLIC_HEADER));
        if (!$publicKey)
        {
            throw new CryptographyException('Invalid public key: ' . openssl_error_string());
        }

        if (!openssl_public_encrypt($content, $encrypted, $publicKey, self::PADDING))
        {
            throw new CryptographyException('Failed to encrypt content: ' . openssl_error_string());
        }

        return base64_encode($encrypted);
    }

    /**
     * Decrypts the provided content using the specified private key.
     *
     * @param string $content The content to be decrypted, encoded in base64.
     * @param string $privateKey The private key for decryption, encoded in base64.
     * @return string The decrypted content, encoded in UTF-8.
     * @throws CryptographyException If the private key is invalid or the decryption fails.
     */
    public static function decryptContent(string $content, string $privateKey): string
    {
        $privateKey = openssl_pkey_get_private(self::derToPem(Utilities::base64decode($privateKey), self::PEM_PRIVATE_HEADER));

        if (!$privateKey)
        {
            throw new CryptographyException('Invalid private key: ' . openssl_error_string());
        }

        if (!openssl_private_decrypt(base64_decode($content), $decrypted, $privateKey, self::PADDING))
        {
            throw new CryptographyException('Failed to decrypt content: ' . openssl_error_string());
        }

        return mb_convert_encoding($decrypted, 'UTF-8', 'auto');
    }

    public static function validatePublicKey(string $publicKey): bool
    {
        try
        {
            $result = openssl_pkey_get_public(self::derToPem(Utilities::base64decode($publicKey), self::PEM_PUBLIC_HEADER));
        }
        catch(InvalidArgumentException)
        {
            return false;
        }

        if($result === false)
        {
            return false;
        }

        return true;
    }

    public static function validatePrivateKey(string $privateKey): bool
    {
        try
        {
            $result = openssl_pkey_get_private(self::derToPem(Utilities::base64decode($privateKey), self::PEM_PRIVATE_HEADER));
        }
        catch(InvalidArgumentException)
        {
            return false;
        }

        if($result === false)
        {
            return false;
        }

        return true;
    }

    /**
     * Generates a random sequence of bytes with a length determined between the specified minimum and maximum.
     *
     * @param int $minLength The minimum length of the generated byte sequence.
     * @param int $maxLength The maximum length of the generated byte sequence.
     * @return string A hexadecimal string representing the random byte sequence.
     * @throws CryptographyException If the random byte generation fails.
     */
    public static function randomKey(int $minLength, int $maxLength): string
    {
        try
        {
            return bin2hex(random_bytes(random_int($minLength, $maxLength)));
        }
        catch(RandomException $e)
        {
            throw new CryptographyException('Failed to generate random bytes: ' . $e->getMessage());
        }
    }

    /**
     * Generates an array of random keys, each with a length within the specified range.
     *
     * @param int $minLength The minimum length for each random key.
     * @param int $maxLength The maximum length for each random key.
     * @param int $amount The number of random keys to generate.
     * @return array An array of randomly generated keys.
     * @throws CryptographyException If the random key generation fails.
     */
    public static function randomKeys(int $minLength, int $maxLength, int $amount): array
    {
        $keys = [];
        for($i = 0; $i < $amount; $i++)
        {
            $keys[] = self::randomKey($minLength, $maxLength);
        }

        return $keys;
    }

    public static function generateEncryptionKey(): string
    {
        try
        {
            return base64_encode(random_bytes(32));
        }
        catch (RandomException $e)
        {
            throw new CryptographyException('Failed to generate encryption key: ' . $e->getMessage());
        }
    }

    /**
     * Encrypts the given content for transport using the provided encryption key.
     *
     * @param string $content The content to be encrypted.
     * @param string $encryptionKey The encryption key used for encrypting the content.
     * @return string The Base64 encoded string containing the IV and the encrypted content.
     * @throws CryptographyException If the IV generation or encryption process fails.
     */
    public static function encryptTransport(string $content, string $encryptionKey): string
    {
        try
        {
            $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        }
        catch (RandomException $e)
        {
            throw new CryptographyException('Failed to generate IV: ' . $e->getMessage());
        }

        $encrypted = openssl_encrypt($content, self::TRANSPORT_ENCRYPTION, base64_decode($encryptionKey), OPENSSL_RAW_DATA, $iv);

        if($encrypted === false)
        {
            throw new CryptographyException('Failed to encrypt transport content: ' . openssl_error_string());
        }

        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypts the given encrypted transport content using the provided encryption key.
     *
     * @param string $encryptedContent The Base64 encoded encrypted content to be decrypted.
     * @param string $encryptionKey The Base64 encoded encryption key used for decryption.
     * @return string The decrypted content as a string.
     * @throws CryptographyException If the decryption process fails.
     */
    public static function decryptTransport(string $encryptedContent, string $encryptionKey): string
    {
        $decodedData = base64_decode($encryptedContent);
        $ivLength = openssl_cipher_iv_length(self::TRANSPORT_ENCRYPTION);

        // Perform decryption
        $decryption = openssl_decrypt(substr($decodedData, $ivLength),
            self::TRANSPORT_ENCRYPTION,
            base64_decode($encryptionKey),
            OPENSSL_RAW_DATA,
            substr($decodedData, 0, $ivLength)
        );

        if($decryption === false)
        {
            throw new CryptographyException('Failed to decrypt transport content: ' . openssl_error_string());
        }

        return $decryption;
    }
}