<?php

namespace Socialbox\Managers;

use PHPUnit\Framework\TestCase;
use Socialbox\Enums\Flags\PeerFlags;

class RegisteredPeerManagerTest extends TestCase
{
    private static $peerUuid;

    public static function setUpBeforeClass(): void
    {
        if(RegisteredPeerManager::usernameExists('test_peer'))
        {
            RegisteredPeerManager::deletePeer(RegisteredPeerManager::getPeerByUsername('test_peer')->getUuid());
        }

        self::$peerUuid = RegisteredPeerManager::createPeer('test_peer', true);
    }

    public static function tearDownAfterClass(): void
    {
        if(RegisteredPeerManager::usernameExists('test_peer'))
        {
            RegisteredPeerManager::deletePeer(RegisteredPeerManager::getPeerByUsername('test_peer')->getUuid());
        }
    }

    public function testEnablePeer()
    {
        RegisteredPeerManager::enablePeer(self::$peerUuid);
        $peer = RegisteredPeerManager::getPeer(self::$peerUuid);

        $this->assertTrue($peer->isEnabled());
    }


    public function testGetPeer()
    {
        $peer = RegisteredPeerManager::getPeer(self::$peerUuid);

        $this->assertEquals('test_peer', $peer->getUsername());
    }

    public function testGetPeerByUsername()
    {
        $peer = RegisteredPeerManager::getPeerByUsername('test_peer');

        $this->assertEquals(self::$peerUuid, $peer->getUuid());
    }

    public function testUsernameExists()
    {
        $this->assertTrue(RegisteredPeerManager::usernameExists('test_peer'));
    }

    public function testDisablePeer()
    {
        RegisteredPeerManager::disablePeer(self::$peerUuid);
        $peer = RegisteredPeerManager::getPeer(self::$peerUuid);

        $this->assertFalse($peer->isEnabled());
    }

    public function testRemoveFlag()
    {
        RegisteredPeerManager::addFlag(self::$peerUuid, PeerFlags::ADMIN);
        $peer = RegisteredPeerManager::getPeer(self::$peerUuid);

        $this->assertTrue($peer->flagExists(PeerFlags::ADMIN));

        RegisteredPeerManager::removeFlag(self::$peerUuid, PeerFlags::ADMIN);
        $peer = RegisteredPeerManager::getPeer(self::$peerUuid);

        $this->assertFalse($peer->flagExists(PeerFlags::ADMIN));
    }
}
