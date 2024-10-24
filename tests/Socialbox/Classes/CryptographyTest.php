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

        print_r($keyPair);
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
        $file_path = __DIR__ . DIRECTORY_SEPARATOR . 'server_public.der';
        $content = "Test Content";

        $encryptedContent = Cryptography::encryptContent($content, file_get_contents($file_path));

        $this->assertIsString($encryptedContent);
        $this->assertNotEquals($content, $encryptedContent);

        print_r($encryptedContent);
    }

    public function testDecryptFromFile()
    {
        $private_key_file = __DIR__ . DIRECTORY_SEPARATOR . 'server_private.der';
        $content = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'server_secret.txt');

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

    public function testRequestSigning()
    {
        $client_private_der = __DIR__ . DIRECTORY_SEPARATOR . 'client_private.der';
        $client_public_der = __DIR__ . DIRECTORY_SEPARATOR . 'client_public.der';
        $content_file = __DIR__ . DIRECTORY_SEPARATOR . 'content.txt';

        $hash = hash('sha1', file_get_contents($content_file));
        $this->assertEquals('fa2415f0735a8aa151195688852178e8fd6e77c5', $hash);

        $signature = Cryptography::signContent($hash, file_get_contents($client_private_der));
        $this->assertEquals("Gcnijq7V8AYXgdk/eP9IswXN7831FevlBNDTKN60Ku7xesPDuPX8e55+38WFGCQ87DbeiIr+61XIDoN4+bTM4Wl0YSUe7oHV9BBnBqGhyZTntDPedUYUomrF3IRcpVRK0SbQSRaYucIp/ZsSHdbQgQBtDCvH5pK1+5g+VK9ZFT16Isvk0PhMjZiLkUYxUklFuzak7agWiS3wllFPqYSM6ri0RF+5I5JbnR9fUAOfhOceax//5H7d2WsdLj6DwtuY+eL5WyHxSmGA04YeQF3JgOGJ3WX2DSH8L0zA7pkGOjz5y1Nu6+0U6KRUXcezU/iM4zy5OJOnD5eJH4pYZizkiA==", $signature);

        $result = Cryptography::verifyContent($hash, $signature, file_get_contents($client_public_der));
        $this->assertTrue($result);
    }

}
