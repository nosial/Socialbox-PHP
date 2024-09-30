<?php

namespace Socialbox\Managers;

use PDOException;
use PHPUnit\Framework\TestCase;
use Socialbox\Abstracts\CacheLayer;
use Socialbox\Exceptions\DatabaseOperationException;
use Socialbox\Managers\VariableManager;

class VariableManagerTest extends TestCase
{
    /**
     * Test the setter method for a variable in the VariableManager class.
     *
     */
    public function testSetVariable(): void
    {
        CacheLayer::getInstance()->clear();

        VariableManager::deleteVariable('test_name');
        VariableManager::setVariable('test_name', 'test_value');
        $this->assertTrue(VariableManager::variableExists('test_name'));
        $this->assertEquals('test_value', VariableManager::getVariable('test_name'));
        VariableManager::deleteVariable('test_name');

        VariableManager::deleteVariable('test_name2');
        VariableManager::setVariable('test_name2', 'test_value2');
        $this->assertTrue(VariableManager::variableExists('test_name2'));
        $this->assertEquals('test_value2', VariableManager::getVariable('test_name2'));
        VariableManager::deleteVariable('test_name2');
    }

}