<?php

    namespace Socialbox\Classes;

    use PHPUnit\Framework\TestCase;
    use Socialbox\Managers\EncryptionRecordsManager;

    class SecuredPasswordTest extends TestCase
    {
        public function testVerifyPassword()
        {
            print("Getting random encryption record\n");
            $encryptionRecord = EncryptionRecordsManager::getRandomRecord();

            print("Securing password\n");
            $securedPassword = SecuredPassword::securePassword('123-123-123', 'password!', $encryptionRecord);

            print("Verifying password\n");
            $this->assertTrue(SecuredPassword::verifyPassword('password!', $securedPassword, EncryptionRecordsManager::getAllRecords()));
        }
    }
