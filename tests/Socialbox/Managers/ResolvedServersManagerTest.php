<?php

namespace Socialbox\Managers;

use DateTime;
use ncc\Utilities\Resolver;
use PHPUnit\Framework\TestCase;
use Socialbox\Classes\ServerResolver;

class ResolvedServersManagerTest extends TestCase
{

    public function setUp(): void
    {
        if(ResolvedServersManager::resolvedServerExists('n64.cc'))
        {
            ResolvedServersManager::deleteResolvedServer('n64.cc');
        }
    }

    public function testGetResolvedServerUpdated()
    {
        ResolvedServersManager::addResolvedServer('n64.cc', ServerResolver::resolveDomain('n64.cc'));
        $this->assertInstanceOf(DateTime::class, ResolvedServersManager::getResolvedServerUpdated('n64.cc'));
    }

    public function testResolvedServerExists()
    {
        ResolvedServersManager::addResolvedServer('n64.cc', ServerResolver::resolveDomain('n64.cc'));
        $this->assertTrue(ResolvedServersManager::resolvedServerExists('n64.cc'));
    }

    public function testGetResolvedServer()
    {
        ResolvedServersManager::addResolvedServer('n64.cc', ServerResolver::resolveDomain('n64.cc'));
        $resolvedServer = ResolvedServersManager::getResolvedServer('n64.cc');

        $this->assertEquals('n64.cc', $resolvedServer->getDomain());
        $this->assertIsString($resolvedServer->getEndpoint());
        $this->assertIsString($resolvedServer->getPublicKey());
        $this->assertInstanceOf(DateTime::class, $resolvedServer->getUpdated());
    }
}
