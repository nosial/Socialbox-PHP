<?php

namespace Socialbox\Classes;

use PHPUnit\Framework\TestCase;
use Socialbox\Exceptions\ResolutionException;
use Socialbox\Objects\ResolvedServer;

class ServerResolverTest extends TestCase
{
    /**
     * Test for the function resolveDomain of the class ServerResolver
     */
    public function testResolveDomain(): void
    {
        // successful resolution
        $resolvedServer = ServerResolver::resolveDomain('n64.cc');
        self::assertNotEmpty($resolvedServer->getEndpoint());
        self::assertNotEmpty($resolvedServer->getPublicKey());
    }
}