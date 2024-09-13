<?php

namespace Socialbox\Classes;

use PHPUnit\Framework\TestCase;
use Socialbox\Exceptions\CryptographyException;

class CryptographyTest extends TestCase
{
    /**
     * Testing `Cryptography::generateKeyPair` method
     * @throws CryptographyException
     */
    public function testGenerateKeyPair()
    {
        $keyPair = Cryptography::generateKeyPair();

        $this->assertIsObject($keyPair);
        $this->assertObjectHasProperty('publicKey', $keyPair);
        $this->assertObjectHasProperty('privateKey', $keyPair);
        $this->assertIsString($keyPair->getPublicKey());
        $this->assertIsString($keyPair->getPrivateKey());
    }

    /**
     * Testing `Cryptography::signContent` method
     * @throws CryptographyException
     */
    public function testSignContent()
    {
        $content = "My secret content";
        $keyPair = Cryptography::generateKeyPair();

        $signature = Cryptography::signContent($content, $keyPair->getPrivateKey());

        $this->assertIsString($signature);
    }

    /**
     * Testing `Cryptography::verifyContent` method
     * @throws CryptographyException
     */
    public function testVerifyContent()
    {
        $content = "My secret content";
        $keyPair = Cryptography::generateKeyPair();

        // Sign the content
        $signature = Cryptography::signContent($content, $keyPair->getPrivateKey());

        // Verify the content
        $result = Cryptography::verifyContent($content, $signature, $keyPair->getPublicKey());

        $this->assertTrue($result);
    }

    /**
     * Testing `Cryptography::temporarySignature` method
     * @throws CryptographyException
     */
    public function testTemporarySignature()
    {
        $content = "Test Content";
        $keyPair = Cryptography::generateKeyPair();

        $tempSignature = Cryptography::temporarySignContent($content, $keyPair->getPrivateKey());

        $this->assertIsString($tempSignature);
    }

    /**
     * Testing `Cryptography::verifyTemporarySignature` method
     * @throws CryptographyException
     */
    public
    function testVerifyTemporarySignature()
    {
        $content = "Test Content";
        $keyPair = Cryptography::generateKeyPair();
        $frames = 2;

        // Generate a temporary signature
        $tempSignature = Cryptography::temporarySignContent($content, $keyPair->getPrivateKey());

        // Verify the temporary signature
        $result = Cryptography::verifyTemporarySignature($content, $tempSignature, $keyPair->getPublicKey(), $frames);

        $this->assertTrue($result);
    }

    /**
     * Testing `Cryptography::encrypt` method
     * @throws CryptographyException
     */
    public function testEncrypt()
    {
        $content = "Test Content";
        $keyPair = Cryptography::generateKeyPair();

        // Encrypt the content
        $encryptedContent = Cryptography::encryptContent($content, $keyPair->getPublicKey());

        $this->assertIsString($encryptedContent);
    }

    /**
     * Testing `Cryptography::decrypt` method
     * @throws CryptographyException
     */
    public function testDecrypt()
    {
        $content = "Test Content";
        $keyPair = Cryptography::generateKeyPair();

        // Encrypt the content
        $encryptedContent = Cryptography::encryptContent($content, $keyPair->getPublicKey());

        // Decrypt the content
        $decryptedContent = Cryptography::decryptContent($encryptedContent, $keyPair->getPrivateKey());

        $this->assertIsString($decryptedContent);
        $this->assertEquals($content, $decryptedContent);
    }

    public function testEncryptFromFile()
    {
        $file_path = __DIR__ . DIRECTORY_SEPARATOR . 'public.der';
        $content = "Test Content";

        $encryptedContent = Cryptography::encryptContent($content, file_get_contents($file_path));

        $this->assertIsString($encryptedContent);
        $this->assertNotEquals($content, $encryptedContent);

        print_r($encryptedContent);
    }

    public function testDecryptFromFile()
    {
        $private_key_file = __DIR__ . DIRECTORY_SEPARATOR . 'private.der';
        $content = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'secret.txt');

        try
        {
            $decryptedContent = Cryptography::decryptContent($content, file_get_contents($private_key_file));
        }
        catch(CryptographyException $e)
        {
            $this->fail($e->getMessage());
        }

        $this->assertIsString($decryptedContent);
        $this->assertEquals($decryptedContent, 'Test Content');
    }

    /**
     * Testing `Cryptography::validatePublicKey` method
     */
    public function testValidatePublicKey()
    {
        $keyPair = Cryptography::generateKeyPair();

        $result = Cryptography::validatePublicKey($keyPair->getPublicKey());
        $this->assertTrue($result);

        $resultWithInValidKey = Cryptography::validatePublicKey('invalidKey');

        $this->assertFalse($resultWithInValidKey);
    }

    public function testValidateInvalidPublicKey()
    {
        $result = Cryptography::validatePublicKey('Bogus Key');
        $this->assertFalse($result);

        $result = Cryptography::validatePublicKey(Utilities::base64encode('Bogus Key'));
        $this->assertFalse($result);
    }

    public function testValidatePrivateKey()
    {
        $keyPair = Cryptography::generateKeyPair();

        $result = Cryptography::validatePrivateKey($keyPair->getPrivateKey());
        $this->assertTrue($result);

        $resultWithInValidKey = Cryptography::validatePublicKey('invalidKey');

        $this->assertFalse($resultWithInValidKey);
    }

    public function testValidateInvalidPrivateKey()
    {
        $result = Cryptography::validatePublicKey('Bogus Key');
        $this->assertFalse($result);

        $result = Cryptography::validatePrivateKey(Utilities::base64encode('Bogus Key'));
        $this->assertFalse($result);
    }

}
