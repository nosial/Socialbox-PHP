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
        $this->assertArrayHasKey('host', $config['database']);
        $this->assertIsString($config['database']['host']);

        $this->assertArrayHasKey('port', $config['database']);
        $this->assertIsInt($config['database']['port'], 3306);

        $this->assertArrayHasKey('username', $config['database']);
        $this->assertIsString($config['database']['username']);

        $this->assertArrayHasKey('password', $config['database']);
        $this->assertIsString($config['database']['password']);

        $this->assertArrayHasKey('name', $config['database']);
        $this->assertIsString($config['database']['name']);
    }
}