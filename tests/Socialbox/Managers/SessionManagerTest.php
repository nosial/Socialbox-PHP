<?php

namespace Socialbox\Managers;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Socialbox\Classes\Cryptography;
use Socialbox\Classes\Utilities;
use Socialbox\Objects\SessionRecord;

class SessionManagerTest extends TestCase
{
    public function testCreateSessionWithEmptyPublicKey(): void
    {
        $publicKey = '';

        $this->expectException(InvalidArgumentException::class);
        SessionManager::createSession($publicKey);
    }

    public function testCreateSession(): void
    {
        $keyPair = Cryptography::generateKeyPair();
        $uuid = SessionManager::createSession($keyPair->getPublicKey());

        $this->assertTrue(SessionManager::sessionExists($uuid));
    }

    public function testGetSessionWithValidUuid(): void
    {
        $keyPair = Cryptography::generateKeyPair();
        $uuid = SessionManager::createSession($keyPair->getPublicKey());

        $session = SessionManager::getSession($uuid);

        $this->assertInstanceOf(SessionRecord::class, $session);
        $this->assertEquals($uuid, $session->getUuid());
        $this->assertEquals($keyPair->getPublicKey(), Utilities::base64encode($session->getPublicKey()));
    }
}
