<?php

    namespace Socialbox;

    use Exception;
    use Helper;
    use PHPUnit\Framework\TestCase;

    class SocialClientTest extends TestCase
    {

        public function testCoffeePing(): void
        {
            try
            {
                $rpcClient = new SocialClient(Helper::generateRandomPeer(COFFEE_DOMAIN, prefix: 'pingTest'));
                $this->assertTrue($rpcClient->ping(), sprintf('Failed to ping %s', COFFEE_DOMAIN));
            }
            catch (Exception $e)
            {
                $this->fail('Failed to create RPC client: ' . $e->getMessage());
            }
        }

        public function testTeapotPing(): void
        {
            try
            {
                $rpcClient = new SocialClient(Helper::generateRandomPeer(TEAPOT_DOMAIN, prefix: 'pingTest'));
                $this->assertTrue($rpcClient->ping(), sprintf('Failed to ping %s', TEAPOT_DOMAIN));
            }
            catch (Exception $e)
            {
                $this->fail('Failed to create RPC client: ' . $e->getMessage());
            }
        }
    }
