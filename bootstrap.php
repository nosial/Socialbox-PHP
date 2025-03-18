<?php

    /** @noinspection PhpDefineCanBeReplacedWithConstInspection */

    use Socialbox\Classes\ServerResolver;

    require 'ncc';
    import('net.nosial.socialbox');


    // Definitions for the test environment
    if(!defined('SB_TEST'))
    {
        $dockerTestPath = __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'docker' . DIRECTORY_SEPARATOR;
        $helperClassPath = __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'Helper.php';

        if(!file_exists($dockerTestPath))
        {
            throw new RuntimeException('Docker test path not found: ' . $dockerTestPath);
        }

        if(!file_exists($helperClassPath))
        {
            throw new RuntimeException('Helper class not found: ' . $helperClassPath);
        }

        require $helperClassPath;

        // global
        define('SB_TEST', 1);
        putenv('LOG_LEVEL=debug');

        // coffee.com
        define('COFFEE_DOMAIN', 'coffee.com');
        define('COFFEE_RPC_HOST', '172.17.0.1');
        define('COFFEE_RPC_PORT', 8086);
        define('COFFEE_RPC_SSL', false);
        define('COFFEE_PUBLIC_KEY', file_get_contents($dockerTestPath . 'coffee' . DIRECTORY_SEPARATOR . 'signature.pub'));
        define('COFFEE_PRIVATE_KEY', file_get_contents($dockerTestPath . 'coffee' . DIRECTORY_SEPARATOR .   'signature.pk'));

        // teapot.com
        define('TEAPOT_DOMAIN', 'teapot.com');
        define('TEAPOT_RPC_HOST', '172.17.0.1');
        define('TEAPOT_RPC_PORT', 8087);
        define('TEAPOT_RPC_SSL', false);
        define('TEAPOT_PUBLIC_KEY', file_get_contents($dockerTestPath . 'teapot' . DIRECTORY_SEPARATOR . 'signature.pub'));
        define('TEAPOT_PRIVATE_KEY', file_get_contents($dockerTestPath . 'teapot' . DIRECTORY_SEPARATOR . 'signature.pk'));

        // Define mocked dns server records for testing purposes
        ServerResolver::addMock(COFFEE_DOMAIN, sprintf('v=socialbox;sb-rpc=%s://%s:%d/;sb-key=%s;sb-exp=0', (COFFEE_RPC_SSL ? 'https' : 'http'), COFFEE_RPC_HOST, COFFEE_RPC_PORT, COFFEE_PUBLIC_KEY));
        ServerResolver::addMock(TEAPOT_DOMAIN, sprintf('v=socialbox;sb-rpc=%s://%s:%d/;sb-key=%s;sb-exp=0', (TEAPOT_RPC_SSL ? 'https' : 'http'), TEAPOT_RPC_HOST, TEAPOT_RPC_PORT, TEAPOT_PUBLIC_KEY));
    }