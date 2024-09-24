<?php

namespace Socialbox\Classes;

use PHPUnit\Framework\TestCase;

/**
 * Socialbox's Configuration Test Class
 *
 * This is a test suite for testing the "getConfiguration" method in the "Configuration" class.
 */
class ConfigurationTest extends TestCase
{
    /**
     * Test the "getConfiguration" method in "Configuration" class.
     */
    public function testGetConfiguration(): void
    {
        $config = Configuration::getConfiguration();
        $this->assertIsArray($config, "Configuration should be an array.");

        //Assert that all the default configuration exists
        $this->assertArrayHasKey('database.host', $config);
        $this->assertEquals($config['database.host'], '127.0.0.1');

        $this->assertArrayHasKey('database.port', $config);
        $this->assertEquals($config['database.port'], 3306);

        $this->assertArrayHasKey('database.username', $config);
        $this->assertEquals($config['database.username'], 'root');

        $this->assertArrayHasKey('database.password', $config);
        $this->assertNull($config['database.password'], 'null should be the value of database.password.');

        $this->assertArrayHasKey('database.name', $config);
        $this->assertEquals($config['database.name'], 'test');
    }
}