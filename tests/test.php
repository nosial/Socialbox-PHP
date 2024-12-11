<?php

    use Socialbox\Classes\SecuredPassword;
    use Socialbox\Managers\EncryptionRecordsManager;

    require 'ncc';
    import('net.nosial.socialbox');

    print("Getting random encryption record\n");
    $encryptionRecord = EncryptionRecordsManager::getRandomRecord();
    var_dump($encryptionRecord);

    print("Securing password\n");
    $securedPassword = SecuredPassword::securePassword('123-123-123', 'password!', $encryptionRecord);

    print("Verifying password\n");
    if(SecuredPassword::verifyPassword('password!', $securedPassword, EncryptionRecordsManager::getAllRecords()))
    {
        print("Password verified\n");
    }
    else
    {
        print("Password not verified\n");
    }